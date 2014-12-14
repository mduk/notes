<?php

namespace Mduk;

use Mduk\Mapper\Pdo as PdoMapper;
use Mduk\Identity\Stub as IdentityStub;

class NoteMapper extends PdoMapper {
	protected $table = 'note';
	protected $findSelect = array( 'note_id', 'user_id' );
	protected $countSelect = 'COUNT( note_id )';

	protected function mapIdentity( $source ) {
		return new IdentityStub( 'urn:user:' . $source->user_id . ':note:' . $source->note_id );
	}

	protected function mapLazy( $source ) {
		return new LazyLoader( array( $this, 'loadOneByNoteId' ), array( $source->note_id ) );
	}

	protected function mapObject( $source ) {
		$note = new Note;
		$note->note_id = $source->note_id;
		$note->user_id = $source->user_id;
		$note->body = $source->body;

		$note->user = $this->getMapper( '\\Mduk\\UserMapper' )
			->findOneByUserId( $source->user_id );

		return $note;
	}
}
