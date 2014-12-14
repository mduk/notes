<?php

namespace Mduk;

use Mduk\Mapper\Pdo as PdoMapper;
use Mduk\Identity\Stub as IdentityStub;

class UserMapper extends PdoMapper {
	protected $table = 'user';
	protected $findSelect = array( 'user_id' );
	protected $countSelect = 'COUNT( user_id )';

	protected function mapIdentity( $source ) {
		return new IdentityStub( 'urn:user:' . $source->user_id );
	}

	protected function mapLazy( $source ) {
		return new LazyLoader( array( $this, 'loadOneByUserId' ), array( $source->user_id ) );
	}

	protected function mapObject( $source ) {
		$user = new User;
		$user->user_id = $source->user_id;
		$user->name = $source->name;
		$user->email = $source->email;
		$user->role = $source->role;

		$user->note = $this->getMapper( '\\Mduk\\NoteMapper' )
			->lazyByUserId( $source->user_id );

		return $user;
	}
}
