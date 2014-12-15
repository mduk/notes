<?php

namespace Mduk;

use Mduk\Repository\Factory as RepositoryFactory;
use Mduk\Mapper\Factory as MapperFactory;


class RepositoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetUser() {
		global $pdo;

		$mapperFactory = new MapperFactory( $pdo );
		$repositoryFactory = new RepositoryFactory( $mapperFactory );
		$userRepository = $repositoryFactory->get( '\\Mduk\\User\\Repository' );

		$user = $userRepository->query() // Query
			->limit(1) // Query
			->where() // ConditionSet
				->field( 'name' ) // Condition
				->is( 'Daniel' ) // ConditionSet
			->done()
			->load()
			->shift();

		$this->assertTrue( $user instanceof User );
		$this->assertEquals( 'Daniel', $user->name );
		$this->assertEquals( 'admin', $user->role );
	}

}

