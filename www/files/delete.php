<?php
/*
 * ===========================================================================
 * 파일 삭제 API 예제
 * ===========================================================================
 *
 * 파일 삭제 : "files delete" API통해서 파일를 삭제합니다.
 *
 * ---------------------------------------------------------------------------
 * 작성자: 리성림 <chenglin@smileserv.com>
 * 작성일: 2018년 06월 07일
 * ===========================================================================
 */


/*
 * 프레임워크 파일을 불러옵니다.
 */
include_once '../inc/config.inc' ;

$deleteArray = $_POST['filesKeys'] ;
if ( empty ( $deleteArray ) )
	exit ( 'No files keys' ) ;

$token = $_POST['token'] ;
if ( ! $token )
	exit ( 'No token' ) ;

/*
 * 파일 삭제 API : files delete
 */
$re = $AUTH -> filesDelete ( $token , $deleteArray ) ;
if ( $re )
	if ( isset ( $re -> Error ) )
		echo json_encode($re -> RequestID . ' : ' . $re -> Message) ;
	else if ( isset ( $re -> Result ) )
		echo json_encode($re -> Result) ;
	else
		echo 'Files delete error' ;
else
	echo 'Files delete error' ;
exit ;
?>
