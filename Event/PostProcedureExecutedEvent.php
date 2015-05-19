<?php
namespace TFox\DbProcedureBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TFox\DbProcedureBundle\Procedure\ProcedureInterface;

class PostProcedureExecutedEvent extends Event
{

    /**
     * @var ProcedureInterface
     */
    private $procedure;

    public function __construct(ProcedureInterface $procedure)
    {
        $this->procedure = $procedure;
    }

    /**
     * @return ProcedureInterface
     */
    public function getProcedure()
    {
        return $this->procedure;
    }

    /**
     * @param ProcedureInterface $procedure
     */
    public function setProcedure($procedure)
    {
        $this->procedure = $procedure;
    }



}