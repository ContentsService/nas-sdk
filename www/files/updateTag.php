<?php
/*
 * ===========================================================================
 * 파일 태그 수정 API 예제
 * ===========================================================================
 *
 * 파일 태그 수정 : "files tag update" API통해서 파일 태그를 수정합니다.
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

$arr = json_decode($_POST['json'],true);

$filesKey = $arr['filesKey'] ;
if ( ! $filesKey )
	exit ( 'No files key' ) ;

$tag = $arr['tag'] ;

$token = $arr['token'] ;
if ( ! $token )
	exit ( 'No token' ) ;

/*
* 파일 태그 수정 API : files tag update
*/
$re = $AUTH -> tagUpdate ( $token , $filesKey , $tag ) ;
if ( $re )
	if ( isset ( $re -> Error ) )
		echo json_encode($re -> RequestID . ' : ' .$re -> Message) ;
	else if ( isset ( $re -> Result ) )
		echo json_encode($re -> Result) ;
	else
		echo 'Update tag error' ;
else
	echo 'Update tag error' ;
exit ;
?>
