<?php
/*
 * ===========================================================================
 * 파일 이름 수정 API 예제
 * ===========================================================================
 *
 * 파일 이름 수정 : "files name update" API통해서 파일 이름을 수정합니다.
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

$filesKey = $_POST['filesKey'] ;
if ( ! $filesKey )
	exit ( 'No files key' ) ;

$name = $_POST['filesName'] ;
if ( ! $name )
	exit ( 'No files name' ) ;

$token = $_POST['token'] ;
if ( ! $token )
	exit ( 'No token' ) ;


/*
* 파일 이름 수정 API : files name update
*/
$re = $AUTH -> filesNameUpdate ( $token , $filesKey , $name ) ;
if ( $re )
	if ( isset ( $re -> Error ) )
		echo json_encode($re -> RequestID . ' : ' . $re -> Message );
	else if ( isset ( $re -> Result ) )
		echo json_encode($re -> Result );
	else
		echo 'Update files name error' ;
else
	echo 'Update files name error' ;
exit ;
?>
