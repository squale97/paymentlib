<?php
namespace Pooldevmtdpce\Payment\models;

class OMResponse{
    private bool $status = false;
    private string $message;
    private string $transactionId;

    function __construct($status, $message, $transactionId) {
        $this->status = $status;
        $this->message = $message;
        $this->transactionId = $transactionId;
      }

    function getStatus(): bool {return $this->status;}
    function getMessage(): string {return $this->message;}
    function getTransactionId(): string {return $this->transactionId;}

    function setStatus($status) {
        $this->status = $status;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setTransactionId($transactionId){
        $this->transactionId = $transactionId;
    }
}