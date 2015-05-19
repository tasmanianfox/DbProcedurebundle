<?php
namespace TFox\DbProcedureBundle;

class TFoxDbProcedureEvents
{
    /**
     * Called after procedure was executed
     */
    const PROCEDURE_EXECUTED_POST = 'db.procedure.executed.post';

    /**
     * Called after cursor was fetched
     */
    const CURSOR_FETCHED_POST = 'db.cursor_fetched.post';
}