<?php

namespace App\controller;

use App\Lib\Currency;
use App\Lib\Image;
use App\lib\Login;
use App\lib\Time;
use App\model\TransactionModel;
use App\model\UserModel;
use App\service\DuesService;
use App\service\PaymentService;
use App\service\ReceiptService;
use App\service\UserService;
use App\service\TransactionService;
use Exception;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use UMA\DIC\Container;

class UserController {

    private UserService $userSerivce;
    private TransactionService $transactionService;
    private DuesService $duesService;
    private ReceiptService $receiptService;
    private PaymentService $paymentService;
    private UserModel $user;

    public function __construct(Container  $container) {
        //get the userService from dependency container
        $this->userSerivce = $container->get(UserService::class);
        $this->transactionService = $container->get(TransactionService::class);
        $this->duesService = $container->get(DuesService::class);
        $this->receiptService = $container->get(ReceiptService::class);
        $this->paymentService = $container->get(PaymentService::class);
        $this->user = Login::getLogin();
    }

    public function home($request, $response, $args) {

        // login in user !Note: PLEASE UPDATE THIS
        $user = $this->user;

        // get the query params
        $queryParams = $request->getQueryParams();

        // if page is present then set value to page otherwise to 1
        $page = isset($queryParams['page']) ? $queryParams['page'] : 1;

        $query = isset($queryParams['query']) ? $queryParams['query'] : null;

        // max transaction per page
        $max = 5;

        $view = Twig::fromRequest($request);

        //Get Transaction
        $result = $this->transactionService->findAll($user, $page, $max, $query);

        $transactions = $result['transactions'];

        $currentMonth = Time::thisMonth();
        $nextMonth = Time::nextMonth();

        $currentDue = $this->transactionService->getBalance($user, $currentMonth, $this->duesService);
        $nextDue = $this->transactionService->getBalance($user, $nextMonth, $this->duesService);

        $paymentSettings = $this->paymentService->findById(1);

        $unpaid = $this->transactionService->getUnpaid($user, $this->duesService, $paymentSettings);

        $data = [
            'currentMonth' => $currentMonth,
            'nextMonth' => $nextMonth,
            "currentDue" => Currency::format($currentDue),
            "nextDue" =>  Currency::format($nextDue),
            "unpaid" =>  Currency::format($unpaid['total']),
            'transactions' => $transactions,
            'totalTransaction' => $result['totalTransaction'],
            'transactionPerPage' => $max,
            'currentPage' => $page,
            'query' => $query,
            'totalPages' => ceil(($result['totalTransaction']) / $max),
            'settings' => $paymentSettings,
        ];

        return $view->render($response, 'pages/user-home.html', $data);
    }

    /**
     *  Save user transaction on database
     */
    public function pay($request, $response, $args) {

        $user = $this->user;

        $view = Twig::fromRequest($request);

        $transaction = new TransactionModel();

        $transaction->setAmount($request->getParsedBody()['amount']);
        $transaction->setFromMonth(Time::startMonth($request->getParsedBody()['startDate']));
        $transaction->setToMonth(Time::endMonth($request->getParsedBody()['startDate']));
        $transaction->setCreatedAt(Time::timestamp());

        // set user id to the current login user
        $transaction->setUser($user);

        // gcash receipts sent by user | multiple files
        $images = $_FILES['receipts'];

        // upload path
        $path = './uploads/';

        // save transaction
        $this->transactionService->save($transaction);

        // store physicaly
        $storedImages = Image::storeAll($path, $images);

        // save image to database
        $this->receiptService->saveAll($storedImages, $transaction);

        return $response
            ->withHeader('Location', '/home')
            ->withStatus(302);
    }

    public function test($request, $response, $args) {

        var_dump($this->duesService->getDue('2023-12-01'));
        // var_dump($this->transactionService->findById(1)->getFromMonth());
        // var_dump($this->transactionService->isPaid('2023-01-03'));

        return $response;
    }

    /**
     * View unpaid monthly dues and its total.
     */
    public function dues($request, $response, $args) {

        $view = Twig::fromRequest($request);

        // login in user
        $user = $this->user;

        // Default payment settings is 1
        $paymentSettings = $this->paymentService->findById(1);

        //get arrays of unpaid monhts
        $data = $this->transactionService->getUnpaid($user, $this->duesService, $paymentSettings);

        //since the dues in unpaid month are float. 
        //then format it to have peso value / curreny
        $items = Currency::formatArray($data['items'], 'due');

        return $view->render($response, 'pages/dues-breakdown.html', [
            'items' => $items,
            'total' =>  Currency::format($data['total'])
        ]);
    }


    /**
     * This function retrieves a transaction from the database
     * using the provided ID and displays it to the user.
     *
     * @return The rendered HTML page displaying the transaction.
     */
    public function transaction($request, $response, $args) {

        $view = Twig::fromRequest($request);

        // login in user
        $user = $this->user;

        //get transction from databse base on ID
        $transaction = $this->transactionService->findById($args['id']);

        //Default Payment Settings
        $paymentSettings = $this->paymentService->findById(1);

        //Transactions logs
        $logs = $transaction->getLogs();

        return $view->render($response, 'pages/user-transaction.html', [
            'transaction' => $transaction,
            'receipts' => $transaction->getReceipts(),
            'logs' => $logs,
        ]);
    }
}
