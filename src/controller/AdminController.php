<?php

namespace App\controller;

use App\Lib\Currency;
use App\Lib\Image;
use App\lib\Time;
use App\model\TransactionModel;
use App\model\UserModel;
use App\service\DuesService;
use App\service\ReceiptService;
use App\service\UserService;
use App\service\TransactionService;
use Exception;
use Slim\Psr7\Response;
use Slim\Views\Twig;
use UMA\DIC\Container;

class AdminController {

    private UserService $userSerivce;
    private TransactionService $transactionService;
    private DuesService $duesService;
    private ReceiptService $receiptService;

    public function __construct(Container  $container) {
        //get the userService from dependency container
        $this->userSerivce = $container->get(UserService::class);
        $this->transactionService = $container->get(TransactionService::class);
        $this->duesService = $container->get(DuesService::class);
        $this->receiptService = $container->get(ReceiptService::class);
    }

    public function home($request, $response, $args) {

        // get the query params
        $queryParams = $request->getQueryParams();

        // if page is present then set value to page otherwise to 1
        $page = isset($queryParams['page']) ? $queryParams['page'] : 1;

        $query = isset($queryParams['query']) ? $queryParams['query'] : null;

        // max transaction per page
        $max = 10;

        $view = Twig::fromRequest($request);

        //Get Transaction
        $result = $this->transactionService->getAll($page, $max, $query);

        $transactions = $result['transactions'];

        $data = [
            'transactions' => $transactions,
            'totalTransaction' => $result['totalTransaction'],
            'transactionPerPage' => $max,
            'currentPage' => $page,
            'query' => $query,
            'totalPages' => ceil(($result['totalTransaction']) / $max),
        ];

        return $view->render($response, 'pages/admin-home.html', $data);
    }

    public function transaction($request, $response, $args) {
        $view = Twig::fromRequest($request);

        $transaction = $this->transactionService->findById($args['id']);

        $user = $transaction->getUser();

        return $view->render($response, 'pages/admin-transaction.html', [
            'transaction' => $transaction,
            'receipts' => $transaction->getReceipts(),
            'user' => $user,
        ]);
    }

    public function rejectPayment($request, $response, $args) {

        $view = Twig::fromRequest($request);

        $id = $request->getParsedBody()['id'];

        $transaction = $this->transactionService->findById($id);

        $user = $transaction->getUser();

        $transaction->setStatus('REJECTED');

        $this->transactionService->save($transaction);

        return $response
            ->withHeader('Location', "/admin/transaction/$id")
            ->withStatus(302);
    }
}
