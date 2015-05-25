<?php
namespace TFox\DbProcedureBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TFox\DbProcedureBundle\Procedure\AbstractProcedure;

class PostCursorFetchedEvent extends Event
{

    /**
     * @var AbstractProcedure
     */
    private $procedure;

    /**
     * @var mixed
     */
    private $result;

    public function __construct(AbstractProcedure $procedure, $result)
    {
        $this->procedure = $procedure;
        $this->result = $result;
    }

    /**
     * @return AbstractProcedure
     */
    public function getProcedure()
    {
        return $this->procedure;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param AbstractProcedure $procedure
     */
    public function setProcedure($procedure)
    {
        $this->procedure = $procedure;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }




}