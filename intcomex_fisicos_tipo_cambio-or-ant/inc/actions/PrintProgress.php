<?php

use WP_Queue\Job;


class PrintProgress extends Job {

	/**
	 * @var int
	 */
    public $evento;
	public $cantidad;

	/**
	 * Subscribe_User_Job constructor.
	 *
	 * @param int $user_id
	 */
	public function __construct($evento,  $cantidad ) {
		$this->cantidad = $cantidad;
        $this->evento   = $evento;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        add_to_info_log($this->evento . " [productos afectados $this->cantidad]");
	}
}