<?php
	
	define( 'CRLF', "\r\n" );
	//define( 'HOME', dirname( __FILE__ ).'/' );
	
	//-- security checks
	if ( !is_dir( $_REQUEST[ 'folder' ] ) )
		die( 'Need correct directory' );
	//if ( str_replace( '../', '', $_REQUEST[ 'folder' ] ) != $_REQUEST[ 'folder' ] )
	//	die( 'Nice try.' );

	//-- read directory
	//$d = dir( HOME.$_REQUEST[ 'folder' ] );
	$d = dir( $_REQUEST[ 'folder' ] );
	$p = ( substr( $_REQUEST[ 'folder' ], -1, 1 ) != '/' ) ? $_REQUEST[ 'folder' ].'/' : $_REQUEST[ 'folder' ];
	$f = array();
	
	//-- get files
	while ( false !== ( $file = $d->read() ) )
		if ( strtolower( substr( $file, -3, 3 ) ) == 'mod' )
			$f[ $file ] = strtolower( $file );
			
	//-- sort
	array_multisort( $f, SORT_STRING, SORT_ASC );
	reset( $f );
	
	//-- output xml
	echo '<playlist>'.CRLF;
	foreach ( $f as $file => $val )
		echo chr( 9 ).'<song url="'.$p.$file.'" hasCredits="true" />'.CRLF;
	echo '</playlist>';

	$d->close();
?>