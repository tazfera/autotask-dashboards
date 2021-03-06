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
	App::uses('AutotaskAppModel', 'Autotask.Model');

	class Dashboard extends AutotaskAppModel {

		public $name = 'Dashboard';

		public $hasMany = array(
				'Dashboardqueue' => array(
						'className' => 'Autotask.Dashboardqueue'
					,	'dependent' => true
				)
			,	'Dashboardresource' => array(
						'className' => 'Autotask.Dashboardresource'
					,	'dependent' => true
				)
			,	'Dashboardticketstatus' => array(
						'className' => 'Autotask.Dashboardticketstatus'
					,	'dependent' => true
				)
			,	'Dashboardwidget' => array(
						'className' => 'Autotask.Dashboardwidget'
					,	'dependent' => true
				)
		);

		private $__aCalculatedWidgets = array(
				'kill_rate' => array(
						'database_field' => 'show_kill_rate'
					,	'widget_id' => 1
					,	'type' => ''
					,	'settings' => array(
								'title_new' => 'New'
							,	'title_completed' => 'Completed'
						)
				)
			,	'rolling_week' => array(
						'database_field' => 'show_rolling_week'
					,	'widget_id' => 2
					,	'type' => ''
				)
			,	'queue_health' => array(
						'database_field' => 'show_queue_health'
					,	'widget_id' => 3
					,	'type' => ''
				)
			,	'accounts' => array(
						'database_field' => 'show_accounts'
					,	'widget_id' => 4
					,	'type' => ''
					,	'settings' => array(
								'title_account_name' => 'Account'
							,	'title_amount_of_tickets' => '#'
							,	'title_average_days' => 'Avg. days'
						)
				)
			,	'queues' => array(
						'database_field' => 'show_queues'
					,	'widget_id' => 5
					,	'type' => ''
					,	'settings' => array(
								'title_queue_name' => 'Queue'
							,	'title_amount_of_tickets' => '#'
							,	'title_average_days' => 'Avg. days open'
							,	'title_days_overdue' => '# Overdue'
						)
				)
			,	'resources' => array(
						'database_field' => 'show_resources'
					,	'widget_id' => 6
					,	'type' => ''
					,	'settings' => array(
								'title_hours_worked' => 'Hours worked'
							,	'title_hours_billable' => 'Billable'
							,	'title_resource' => 'Resource'
							,	'title_active_tickets' => 'Active'
							,	'title_closed_today' => 'Closed today'
							,	'title_average_days' => 'Days'
							,	'title_worked' => 'Worked'
						)
				)
			,	'unassigned' => array(
						'database_field' => 'show_unassigned'
					,	'widget_id' => 7
					,	'type' => 'unassigned'
					,	'settings' => array(
							'goal_description' => 'Should be 0'
						)
				)
			,	'sla_violations' => array(
						'database_field' => 'show_sla_violations'
					,	'widget_id' => 7
					,	'type' => 'sla_violations'
					,	'settings' => array(
							'goal_description' => 'Should be 0'
						)
				)
			,	'missing_issue_type' => array(
						'database_field' => 'show_missing_issue_type'
					,	'widget_id' => 7
					,	'type' => 'missing_issue_type'
					,	'settings' => array(
							'goal_description' => 'Should be 0'
						)
				)
			,	'rolling_week_bars' => array(
						'database_field' => 'show_rolling_week_bars'
					,	'widget_id' => 8
					,	'type' => ''
				)
			,	'tickets_top_x' => array(
						'database_field' => 'show_tickets_top_x'
					,	'widget_id' => 9
					,	'type' => ''
					,	'settings' => array(
								'title_created' => 'Created'
							,	'title_name' => 'Name'
							,	'title_number' => 'Number'
						)
				)
			,	'clock' => array(
						'database_field' => 'show_clock'
					,	'widget_id' => 10
					,	'type' => ''
				)
			,	'open_tickets' => array(
						'database_field' => 'show_open_tickets'
					,	'widget_id' => 11
					,	'type' => ''
				)
			,	'tickets_by_source' => array(
						'database_field' => 'show_tickets_by_source'
					,	'widget_id' => 12
					,	'type' => ''
				)
		);

		var $validate = array(
			'slug' => array(
					'notEmpty' => array(
							'rule' => 'notEmpty'
						,	'required' => true
						,	'message' => 'required_field'
						,	'on' => 'create'
					)
				,	'unique' => array(
							'rule' => array( 'checkUniqueSlug' )
						,	'message' => 'URL is already in use.'
					)
			)
		);

		public function checkUniqueSlug( $check ) {

			$aResult = $this->find( 'count', array(
					'conditions' => array(
							'Dashboard.slug' => $check['slug']
						,	'Dashboard.id <>' => $this->data['Dashboard']['id']
					)
				)
			);

			if( 0 == $aResult ) {
				return true;
			}

			return false;

		}


		public function getWidgetData( $iDashboardId ) {

			$aDashboardWidgets = array();

			// First we figure out if the widgets should be listening to any
			// specific queues or statuses.
			$aQueueIds = array();
			$aResourceIds = array();
			$aTicketstatusIds = array();

			if( !empty( $iDashboardId ) ) {

				App::uses( 'Dashboardqueue', 'Autotask.Model' );
				$this->Dashboardqueue = new Dashboardqueue();

				$aQueueIds = $this->Dashboardqueue->find( 'forDashboard', array(
						'conditions' => array(
								'Dashboardqueue.dashboard_id' => $iDashboardId
						)
				) );

				App::uses( 'Dashboardresource', 'Autotask.Model' );
				$this->Dashboardresource = new Dashboardresource();

				$aResourceIds = $this->Dashboardresource->find( 'forDashboard', array(
						'conditions' => array(
								'Dashboardresource.dashboard_id' => $iDashboardId
						)
				) );

				App::uses( 'Dashboardticketstatus', 'Autotask.Model' );
				$this->Dashboardticketstatus = new Dashboardticketstatus();

				$aTicketstatusIds = $this->Dashboardticketstatus->find( 'forDashboard', array(
						'conditions' => array(
								'Dashboardticketstatus.dashboard_id' => $iDashboardId
						)
				) );

			}
			// End

			// Now we fetch the widgets for the dashboard, and for each widget its actual data.
			$aDashboard = $this->find( 'first', array(
					'conditions' => array(
							'Dashboard.id' => $iDashboardId
					)
				,	'contain' => array(
							'Dashboardwidget' => array(
									'Widget'
								,	'Dashboardwidgetsetting'
								,	'order' => 'Dashboardwidget.row ASC'
							)
					)
			) );

			if( !empty( $aDashboard['Dashboardwidget'] ) ) {

				foreach ( $aDashboard['Dashboardwidget'] as $iKey => $aWidget ) {

					switch ( $aWidget['Widget']['id'] ) {

						// Kill Rate
						case 1:

							App::uses( 'Ticket', 'Autotask.Model' );
							$this->Ticket = new Ticket();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Ticket->getKillRate( $aQueueIds )
							) );

						break;

						// Kill Rate History - Graph
						// Kill Rate History - Bars
						case 2:
						case 8:

							App::uses( 'Killratecount', 'Autotask.Model' );
							$this->Killratecount = new Killratecount();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Killratecount->getRollingWeek( $iDashboardId )
							) );

						break;

						// Queue Health Graph
						case 3:

							App::uses( 'Queuehealthcount', 'Autotask.Model' );
							$this->Queuehealthcount = new Queuehealthcount();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Queuehealthcount->getHistory( $iDashboardId )
							) );

						break;

						// Accounts Top X
						case 4:

							App::uses( 'Account', 'Autotask.Model' );
							$this->Account = new Account();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Account->getTotals( $aQueueIds )
							) );

						break;

						// Queues Tables
						case 5:

							App::uses( 'Queue', 'Autotask.Model' );
							$this->Queue = new Queue();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Queue->getTotals( $aQueueIds )
							) );

						break;

						// Resources Tables
						case 6:

							App::uses( 'Resource', 'Autotask.Model' );
							$this->Resource = new Resource();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Resource->getTotals( $aQueueIds, $aResourceIds )
							) );

						break;

						// Ticket Status (count)
						case 7:

							if( !empty( $aWidget['type'] ) ) {

								App::uses( 'Ticket', 'Autotask.Model' );
								$this->Ticket = new Ticket();

								switch ( $aWidget['type'] ) {

									case 'missing_issue_type':

										$aWidget = array_merge( $aWidget, array(
												'Widgetdata' => array(
														'count' => $this->Ticket->getAtes( $aQueueIds )
												)
										) );

									break;

									case 'unassigned':

										$aWidget = array_merge( $aWidget, array(
												'Widgetdata' => $this->Ticket->getUnassignedTotals( $aQueueIds )
										) );

									break;

									case 'sla_violations':

										$aWidget = array_merge( $aWidget, array(
												'Widgetdata' => $this->Ticket->getSLAViolations( $aQueueIds )
										) );

									break;

									default:
									break;

								}

							} elseif( !empty( $aWidget['ticketstatus_id'] ) ) {

								App::uses( 'Ticketstatus', 'Autotask.Model' );
								$this->Ticketstatus = new Ticketstatus();

								$aWidgetData = $this->Ticketstatus->getTotals( $aQueueIds, array( $aWidget['ticketstatus_id'] ) );

								$aWidget = array_merge( $aWidget, array(
										'Widgetdata' => $aWidgetData[$aWidget['ticketstatus_id']]
								) );

							}

						break;


						// 10 latest tickets.
						case 9:

							App::uses( 'Ticket', 'Autotask.Model' );
							$this->Ticket = new Ticket();

							if (!empty($aQueueIds)) {

								$aWidget = array_merge($aWidget, array(
										'Widgetdata' => $this->Ticket->find('all', array(
												'limit' => 11
											,	'order' => 'created DESC'
											,	'conditions' => array(
														'Ticket.queue_id' => $aQueueIds
												)
										))
								));

							} else {

								$aWidget = array_merge($aWidget, array(
										'Widgetdata' => $this->Ticket->find('all', array(
												'limit' => 11
											,	'order' => 'created DESC'
										))
								));

							}

						break;

						// Open tickets.
						case 11:

							App::uses( 'Opentickets', 'Autotask.Model' );
							$this->Opentickets = new Opentickets();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Opentickets->getTotals($aQueueIds)
							) );

						break;

						// Tickets by source
						case 12:
							App::uses( 'Ticketsource', 'Autotask.Model' );
							$this->Ticketsource = new Ticketsource();

							$aWidget = array_merge( $aWidget, array(
									'Widgetdata' => $this->Ticketsource->getTotals($aQueueIds)
							) );
						break;

						default:
						break;

					}

					//  Make sure every widgets looks pretty by filling up empty names.
					if( empty( $aWidget['display_name'] ) ) {

						switch ( $aWidget['type'] ) {

							case 'sla_violations':
								$aWidget['display_name'] = 'SLA Violations';
							break;

							case 'missing_issue_type':
								$aWidget['display_name'] = 'Missing Issue Type';
							break;

							case 'unassigned':
								$aWidget['display_name'] = 'Unassigned';
							break;

							default:
								$aWidget['display_name'] = $aWidget['Widget']['default_name'];
							break;

						}

					}
					// End

					// Put the widget in the output array.
					$aDashboardWidgets[$iKey] = array(
							'Dashboardwidget' => $aWidget
					);

				}

			}
			// End

			return $aDashboardWidgets;

		}


		/**
		 * Since 1.2.0 widgets are seperate database entries.
		 * Whenever you first fire up a dashboard or edit one, all widgets are saved
		 * in the database.
		 * 
		 * @param  integer $iDashboardId - The ID of the dashboard you're editing
		 * @return -
		 */
		public function createDashboardWidgets( Array $aSubmittedData, $aBeforeSaveDashboard = array() ) {

			App::uses( 'Dashboardwidget', 'Autotask.Model' );
			$this->Dashboardwidget = new Dashboardwidget();

			App::uses( 'Dashboardwidgetsetting', 'Autotask.Model' );
			$this->Dashboardwidgetsetting = new Dashboardwidgetsetting();

			App::uses( 'Ticketstatus', 'Autotask.Model' );
			$this->Ticketstatus = new Ticketstatus();

			if( !$this->__updateTicketstatuses( $aSubmittedData, $aBeforeSaveDashboard ) ) {
				return false;
			}

			if( !$this->__updateCalculatedWidgets( $aSubmittedData, $aBeforeSaveDashboard ) ) {
				return false;
			}

			return true;

		}


		public function getLastImportDate() {

			$sFilename = APP . 'tmp/logs/cronjob.log';

			if ( file_exists( $sFilename ) ) {
				return date( "Y-m-d H:i:s", filemtime( $sFilename ) );
			}

			return false;

		}


		/**
		 * When you edit a dashboard, this function takes care of any changes in the
		 * ticketstatus widgets (added or removed ones).
		 * 
		 * @param  Array  $aSubmittedData - Data of the updated dashboard
		 * @param  Array $aBeforeSaveDashboard - The data of the (possible) existing dashboard
		 * @return -
		 */
		private function __updateTicketstatuses( Array $aSubmittedData, Array $aBeforeSaveDashboard ) {

			// You've created a new dashboard
			if( empty( $aBeforeSaveDashboard ) ) {

				$iDashboardId = $aSubmittedData['Dashboard']['id'];

				$aTicketstatusIdsBefore = array();
				$aTicketstatusIdsAfter = Hash::extract( $aSubmittedData, 'Dashboardticketstatus.{n}.ticketstatus_id' );

			// You're updating an existing one
			} else {

				$iDashboardId = $aBeforeSaveDashboard['Dashboard']['id'];

				if( !empty( $aBeforeSaveDashboard['Dashboardticketstatus'] ) ) {
					$aTicketstatusIdsBefore = Hash::extract( $aBeforeSaveDashboard, 'Dashboardticketstatus.{n}.ticketstatus_id' );
				} else {
					$aTicketstatusIdsBefore = array();
				}

				if( !empty( $aSubmittedData['Dashboardticketstatus']['id'] ) ) {
					$aTicketstatusIdsAfter = Hash::extract( $aSubmittedData, 'Dashboardticketstatus.id.{n}' );
				} else {
					$aTicketstatusIdsAfter = array();
				}

			}

			// Were there any statuses removed?
			if( !empty( $aTicketstatusIdsBefore ) ) {

				foreach ( $aTicketstatusIdsBefore as $iTicketstatusId ) {

					if( !in_array( $iTicketstatusId, $aTicketstatusIdsAfter ) ) { // Removed

						if( !$this->Dashboardwidget->deleteAll( array(
								'dashboard_id' => $iDashboardId
							,	'ticketstatus_id' => $iTicketstatusId
							,	'widget_id' => 7
						) ) ) {
							return false;
						}

					}

				}

			}

			// Where there any statuses added?
			if( !empty( $aTicketstatusIdsAfter ) ) {

				foreach ( $aTicketstatusIdsAfter as $iTicketstatusId ) {

					if( !in_array( $iTicketstatusId, $aTicketstatusIdsBefore ) ) { // Added

						$this->Ticketstatus->recursive = -1;
						$aTicketstatus = $this->Ticketstatus->find( 'first', array(
								'conditions' => array(
										'Ticketstatus.id' => $iTicketstatusId
								)
						) );

						$this->Dashboardwidget->create();

						if( !$this->Dashboardwidget->save( array(
								'dashboard_id' => $iDashboardId
							,	'widget_id' => 7
							,	'ticketstatus_id' => $aTicketstatus['Ticketstatus']['id']
							,	'display_name' => $aTicketstatus['Ticketstatus']['name']
						) ) ) {
							return false;
						}

					}

				}

			}

			return true;

		}


		/**
		 * Adds or removes the 'calculated' widgets like the kill rates and queue health.
		 * 
		 * @param  Array  $aSubmittedData - Data of the updated dashboard
		 * @param  mixed $aBeforeSaveDashboard - The data of the (possible) existing dashboard
		 * @return -
		 */
		private function __updateCalculatedWidgets( Array $aSubmittedData, Array $aBeforeSaveDashboard ) {

			// You've created a new dashboard
			if( empty( $aBeforeSaveDashboard ) ) {

				$iDashboardId = $aSubmittedData['Dashboard']['id'];

				foreach ( $this->__aCalculatedWidgets as $aWidget ) {

					// Was '<widget name here>' added?
					if( 1 == $aSubmittedData['Dashboard'][ $aWidget['database_field'] ] ) {

						if( !$this->__updateCalculatedWidget( $iDashboardId, $aWidget ) ) {
							return false;
						}

					}

				}

			// You're updating an existing one
			} else {

				$iDashboardId = $aBeforeSaveDashboard['Dashboard']['id'];

				foreach ( $this->__aCalculatedWidgets as $aWidget ) {

					// Was '<widget name here>' added?
					if(
						1 == $aSubmittedData['Dashboard'][ $aWidget['database_field'] ]
						&&
						false == $aBeforeSaveDashboard['Dashboard'][ $aWidget['database_field'] ]
					) {

						if( !$this->__updateCalculatedWidget( $iDashboardId, $aWidget ) ) {
							return false;
						}

					// Or was '<widget name here>' removed?
					} elseif(
						0 == $aSubmittedData['Dashboard'][ $aWidget['database_field'] ]
						&&
						true == $aBeforeSaveDashboard['Dashboard'][ $aWidget['database_field'] ]
					) {

						// Cascade deletes the old widget
						if( !$this->Dashboardwidget->deleteAll( array(
								'dashboard_id' => $iDashboardId
							,	'widget_id' => $aWidget['widget_id']
						) ) ) {
							return false;
						}

					}

				}

			}

			return true;

		}


		/**
		 * Updates or adds a calculated widget to a dashboard.
		 * 
		 * @param  integer $iDashboardId - The ID of the dashboard
		 * @param  Array  $aWidget - The widget you're trying to link
		 * @return bool - true on success, false on error.
		 */
		private function __updateCalculatedWidget( $iDashboardId, Array $aWidget ) {

			$this->Dashboardwidget->create();
			if( !$this->Dashboardwidget->save( array(
					'dashboard_id' => $iDashboardId
				,	'widget_id' => $aWidget['widget_id']
				,	'type' => $aWidget['type']
			) ) ) {

				return false;

			} else {

				if( !empty( $aWidget['settings'] ) ) {

					foreach ( $aWidget['settings'] as $sName => $sValue ) {

						$this->Dashboardwidgetsetting->create();
						if( !$this->Dashboardwidgetsetting->save( array(
								'dashboardwidget_id' => $this->Dashboardwidget->id
							,	'name' => $sName
							,	'value' => $sValue
						) ) ) {

							return false;

						}

					}

				}

			}

			return true;

		}

	}