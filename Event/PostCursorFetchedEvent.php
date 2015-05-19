<?php
namespace TFox\DbProcedureBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TFox\DbProcedureBundle\Procedure\ProcedureInterface;

class PostCursorFetchedEvent extends Event
{

    /**
     * @var ProcedureInterface
     */
    private $procedure;

    /**
     * @var mixed
     */
    private $result;

    public function __construct(ProcedureInterface $procedure, $result)
    {
        $this->procedure = $procedure;
        $this->result = $result;
    }

    /**
     * @return ProcedureInterface
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
     * @param ProcedureInterface $procedure
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