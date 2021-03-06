<?php

/**
 * @covers EchoNotificationMapper
 */
class EchoNotificationMapperTest extends MediaWikiTestCase {

	/**
	 * @todo write this test
	 */
	public function testInsert() {
		$this->assertTrue( true );
	}

	public function fetchUnreadByUser( User $user, $limit, array $eventTypes = [] ) {
		// Unsuccessful select
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'select' => false ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '' );
		$this->assertEmpty( $res );

		// Successful select
		$dbResult = [
			(object)[
				'event_id' => 1,
				'event_type' => 'test_event',
				'event_variant' => '',
				'event_extra' => '',
				'event_page_id' => '',
				'event_agent_id' => '',
				'event_agent_ip' => '',
				'notification_user' => 1,
				'notification_timestamp' => '20140615101010',
				'notification_read_timestamp' => '',
				'notification_bundle_base' => 1,
				'notification_bundle_hash' => 'testhash',
				'notification_bundle_display_hash' => 'testdisplayhash'
			]
		];
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'select' => $dbResult ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '', [] );
		$this->assertEmpty( $res );

		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'select' => $dbResult ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '', [ 'test_event' ] );
		$this->assertInternalType( 'array', $res );
		$this->assertNotEmpty( $res );
		foreach ( $res as $row ) {
			$this->assertInstanceOf( EchoNotification::class, $row );
		}
	}

	public function testFetchByUser() {
		// Unsuccessful select
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'select' => false ] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '' );
		$this->assertEmpty( $res );

		// Successful select
		$notifDbResult = [
			(object)[
				'event_id' => 1,
				'event_type' => 'test_event',
				'event_variant' => '',
				'event_extra' => '',
				'event_page_id' => '',
				'event_agent_id' => '',
				'event_agent_ip' => '',
				'event_deleted' => 0,
				'notification_user' => 1,
				'notification_timestamp' => '20140615101010',
				'notification_read_timestamp' => '20140616101010',
				'notification_bundle_base' => 1,
				'notification_bundle_hash' => 'testhash',
				'notification_bundle_display_hash' => 'testdisplayhash'
			]
		];

		$tpDbResult = [
			(object)[
				'etp_page' => 7, // pageid
				'etp_event' => 1, // eventid
			],
		];

		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'select' => $notifDbResult ] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '', [] );
		$this->assertEmpty( $res );

		$notifMapper = new EchoNotificationMapper(
			$this->mockMWEchoDbFactory( [ 'select' => $notifDbResult ] ),
			new EchoTargetPageMapper(
				$this->mockMWEchoDbFactory( [ 'select' => $tpDbResult ] )
			)
		);
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '', [ 'test_event' ] );
		$this->assertInternalType( 'array', $res );
		$this->assertNotEmpty( $res );
		foreach ( $res as $row ) {
			$this->assertInstanceOf( EchoNotification::class, $row );
		}

		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '' );
		$this->assertEmpty( $res );
	}

	public function testFetchNewestByUserBundleHash() {
		// Unsuccessful select
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'selectRow' => false ] ) );
		$res = $notifMapper->fetchNewestByUserBundleHash( User::newFromId( 1 ), 'testhash' );
		$this->assertFalse( $res );

		// Successful select
		$dbResult = (object)[
			'event_id' => 1,
			'event_type' => 'test',
			'event_variant' => '',
			'event_extra' => '',
			'event_page_id' => '',
			'event_agent_id' => '',
			'event_agent_ip' => '',
			'event_deleted' => 0,
			'notification_user' => 1,
			'notification_timestamp' => '20140615101010',
			'notification_read_timestamp' => '20140616101010',
			'notification_bundle_base' => 1,
			'notification_bundle_hash' => 'testhash',
			'notification_bundle_display_hash' => 'testdisplayhash'
		];
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'selectRow' => $dbResult ] ) );
		$row = $notifMapper->fetchNewestByUserBundleHash( User::newFromId( 1 ), 'testdisplayhash' );
		$this->assertInstanceOf( EchoNotification::class, $row );
	}

	public function testFetchByUserOffset() {
		// Unsuccessful select
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'selectRow' => false ] ) );
		$res = $notifMapper->fetchByUserOffset( User::newFromId( 1 ), 500 );
		$this->assertFalse( $res );

		// Successful select
		$dbResult = (object)[
			'event_id' => 1,
			'event_type' => 'test',
			'event_variant' => '',
			'event_extra' => '',
			'event_page_id' => '',
			'event_agent_id' => '',
			'event_agent_ip' => '',
			'event_deleted' => 0,
			'notification_user' => 1,
			'notification_timestamp' => '20140615101010',
			'notification_read_timestamp' => '20140616101010',
			'notification_bundle_base' => 1,
			'notification_bundle_hash' => 'testhash',
			'notification_bundle_display_hash' => 'testdisplayhash'
		];
		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( [ 'selectRow' => $dbResult ] ) );
		$row = $notifMapper->fetchByUserOffset( User::newFromId( 1 ), 500 );
		$this->assertInstanceOf( EchoNotification::class, $row );
	}

	public function testDeleteByUserEventOffset() {
		$this->setMwGlobals( [ 'wgUpdateRowsPerQuery' => 4 ] );
		$mockDb = $this->getMockBuilder( DatabaseMysqli::class )
			->disableOriginalConstructor()
			->getMock();
		$mockDb->expects( $this->any() )
			->method( 'selectFieldValues' )
			->will( $this->returnValue( [ 1, 2, 3, 5, 8, 13, 21, 34, 55, 89 ] ) );
		$mockDb->expects( $this->exactly( 3 ) )
			->method( 'delete' )
			->withConsecutive(
				[
					$this->equalTo( 'echo_notification' ),
					$this->equalTo( [ 'notification_user' => 1, 'notification_event' => [ 1, 2, 3, 5 ] ] ),
					$this->anything()
				],
				[
					$this->equalTo( 'echo_notification' ),
					$this->equalTo( [ 'notification_user' => 1, 'notification_event' => [ 8, 13, 21, 34 ] ] ),
					$this->anything()
				],
				[
					$this->equalTo( 'echo_notification' ),
					$this->equalTo( [ 'notification_user' => 1, 'notification_event' => [ 55, 89 ] ] ),
					$this->anything()
				]
			)
			->will( $this->returnValue( true ) );

		$notifMapper = new EchoNotificationMapper( $this->mockMWEchoDbFactory( $mockDb ) );
		$this->assertTrue( $notifMapper->deleteByUserEventOffset( User::newFromId( 1 ), 500 ) );
	}

	/**
	 * Mock object of User
	 */
	protected function mockUser() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'getID' )
			->will( $this->returnValue( 1 ) );
		$user->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnValue( true ) );
		$user->expects( $this->any() )
			->method( 'getGroups' )
			->will( $this->returnValue( [ 'echo_group' ] ) );

		return $user;
	}

	/**
	 * Mock object of EchoNotification
	 */
	protected function mockEchoNotification() {
		$event = $this->getMockBuilder( EchoNotification::class )
			->disableOriginalConstructor()
			->getMock();
		$event->expects( $this->any() )
			->method( 'toDbArray' )
			->will( $this->returnValue( [] ) );

		return $event;
	}

	/**
	 * Mock object of MWEchoDbFactory
	 * @param array|\Wikimedia\Rdbms\IDatabase $dbResultOrMockDb
	 */
	protected function mockMWEchoDbFactory( $dbResultOrMockDb ) {
		$mockDb = is_array( $dbResultOrMockDb ) ? $this->mockDb( $dbResultOrMockDb ) : $dbResultOrMockDb;
		$dbFactory = $this->getMockBuilder( MWEchoDbFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$dbFactory->expects( $this->any() )
			->method( 'getEchoDb' )
			->will( $this->returnValue( $mockDb ) );

		return $dbFactory;
	}

	/**
	 * Returns a mock database object
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	protected function mockDb( array $dbResult ) {
		$dbResult += [
			'insert' => '',
			'select' => '',
			'selectRow' => '',
			'delete' => ''
		];

		$db = $this->getMockBuilder( DatabaseMysqli::class )
			->disableOriginalConstructor()
			->getMock();
		$db->expects( $this->any() )
			->method( 'insert' )
			->will( $this->returnValue( $dbResult['insert'] ) );
		$db->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( $dbResult['select'] ) );
		$db->expects( $this->any() )
			->method( 'delete' )
			->will( $this->returnValue( $dbResult['delete'] ) );
		$db->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $dbResult['selectRow'] ) );
		$db->expects( $this->any() )
			->method( 'onTransactionIdle' )
			->will( new EchoExecuteFirstArgumentStub );

		return $db;
	}

}
