<?php

namespace CirrusSearch\Assignment;

class ConstantAssignment implements ClusterAssignment {
	/** @var string[]|array[] Elastica connection configuration */
	private $servers;

	/**
	 * @param string[]|array[] $servers Elastica connection configuration
	 */
	public function __construct( array $servers ) {
		$this->servers = $servers;
	}

	/**
	 * @param string|null $cluster
	 * @return string[]|array[]
	 */
	public function getServerList( $cluster = null ): array {
		return $this->servers;
	}

	public function getSearchCluster() {
		return 'default';
	}

	public function getWritableClusters(): array {
		return [ 'default' ];
	}

	public function getCrossClusterName() {
		return null;
	}
}
