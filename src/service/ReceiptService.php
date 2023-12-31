<?php

namespace App\service;

use App\model\ReceiptModel;
use App\model\TransactionModel;
use Doctrine\ORM\EntityManager;

class ReceiptService extends Service {

    /**
     * Save model to database
     * @param ReceiptModel $receipt 
     * @return void
     */
    
    public function save(ReceiptModel $receipt) {
        $this->entityManager->persist($receipt);
        $this->entityManager->flush($receipt);
    }


    public function saveAll($receipts,TransactionModel $transaction) {

        foreach($receipts as $imageName){

            $receipt = new ReceiptModel();
            $receipt->setPath($imageName);
            $receipt->setTransaction($transaction);

            $this->save($receipt);
        }

    }

    /**
     * Confirm the reference number of the receipt
     */
    public function confirm(ReceiptModel $receipt,$reference){
        $receipt->setReferenceNumber($reference);
        $this->save($receipt);
    }

    public function findById($id): ReceiptModel {
        $em = $this->entityManager;
        $receipt = $em->find(ReceiptModel::class, $id);
        return $receipt;
    }

    public function precheck($reference){
        $em = $this->entityManager;

        $qb = $em->createQueryBuilder();

        $qb->select('count(u.referenceNumber)')
            ->from(ReceiptModel::class, 'u')
            ->where($qb->expr()->eq('u.referenceNumber','reference'))
            ->setParameter('reference', $reference);

        $query = $qb->getQuery();
        $result = $query->getSingleScalarResult();

        return  $result;

    }


}
