<?php
namespace TFox\DbProcedureBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TFox\DbProcedureBundle\Procedure\AbstractProcedure;

class PostProcedureExecutedEvent extends Event
{

    /**
     * @var AbstractProcedure
     */
    private $procedure;

    public function __construct(AbstractProcedure $procedure)
    {
        $this->procedure = $procedure;
    }

    /**
     * @return AbstractProcedure
     */
    public function getProcedure()
    {
        return $this->procedure;
    }

    /**
     * @param AbstractProcedure $procedure
     */
    public function setProcedure($procedure)
    {
        $this->procedure = $procedure;
    }



}