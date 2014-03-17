<?php
	/**
	 * Copyright 2013, Campai Business Solutions B.V. (http://www.campai.nl)
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice.
	 *
	 * @copyright     Copyright 2013, Campai Business Solutions B.V. (http://www.campai.nl)
	 * @link          http://autotask.campai.nl
	 * @license       MIT License (http://opensource.org/licenses/mit-license.php)
	 * @author        Coen Coppens <coen@campai.nl>
	 */
	class GetTicketsOpenTask extends ImportFromAutotaskShell {

		public $uses = array(
				'Autotask.Ticket'
			,	'Autotask.Dashboardqueue'
		);

		public function execute() {

			// We only fetch open tickets that go back 1 year (default).
			// This prevents recurring tickets from being included, which often leads
			// to insane amount of tickets.
			$aDates = array(
					date('Y-m-d')
			);

			if ($this->params['full']) {

				if (!$iAmountOfDays = Configure::read('Import.OpenTickets.history')) {
					$iAmountOfDays = 365;
				}

				for ($i=1; $i <= $iAmountOfDays; $i++) { 
					$aDates[] = date('Y-m-d', strtotime('-' . $i . ' days'));
				}

			}
			// End

			// Basic conditions.
			$aConditions = array();

			// Add the queues.
			foreach (Hash::extract($this->Dashboardqueue->find('all'), '{n}.Dashboardqueue.queue_id') as $iKey => $iQueueId) {

				$aQueueCondition = array(
						'field' => array(
								'expression' => array(
										'@op' => 'equals',
										'@' => $iQueueId
								),
								'@' => 'QueueID'
						)
				);

				if (0 != $iKey) {
					$aQueueCondition['@operator'] = 'OR';
				}

				$aConditions[] = $aQueueCondition;

			}
			// End

			// Add the dates.
			if (1 < count($aDates)) {

				$aDatesConditions = array(
						'@operator' => 'AND',
						'condition' => array()
				);

				foreach ($aDates as $iKey => $sDate) {

					$aDateCondition = array(
							'field' => array(
									'expression' => array(
											'@op' => 'isthisday',
											'@' => $sDate
									),
									'@' => 'CreateDate'
							)
					);

					if (0 != $iKey) {
						$aDateCondition['@operator'] = 'OR';
					}

					$aConditions[] = $aDateCondition;

				}

			} else {

				$aDatesConditions = array(
						'@operator' => 'AND',
						'field' => array(
								'expression' => array(
										'@op' => 'isthisday',
										'@' => $aDates[0]
								),
								'@' => 'CreateDate'
						)
				);

			}

			$aConditions[] = $aDatesConditions;
			// End

			$oResult = $this->Ticket->findInAutotask('open', $aConditions);
			return $oResult;

		}

	}