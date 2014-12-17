<?php

namespace Mduk\User;

use Mduk\Repository as RepositoryInterface;
use Mduk\Repository\Factory as RepositoryFactory;
use Mduk\Mapper\Factory as MapperFactory;
use Mduk\Identity;

class Repository implements RepositoryInterface {

	protected $repositoryFactory;
	protected $mapperFactory;

	public function __construct( RepositoryFactory $repositoryFactory, MapperFactory $mapperFactory ) {
		$this->repositoryFactory = $repositoryFactory;
		$this->mapperFactory = $mapperFactory;
		$this->userMapper = $this->mapperFactory->get( '\\Mduk\\User\\Mapper' );
	}

	public function query() {
		return $this->userMapper->query();
	}

	public function retrieve( Identity $identity ) {
		$mapper = $this->mapperFactory->get( '\\Mduk\\User\\Mapper' );
		$urn = $identity->getIdentity();

		$numericId = substr( $urn, 10 ); // Totally legit

		$found = $mapper->loadByUserId( $numericId );
		return $found->shift();
	}
}

