<?php
/*
 * ===========================================================================
 * Authentication 클래스 예제
 * ===========================================================================
 *
 * API서버로 접근 내용을 메소드로 구성하여 include해서 사용하면 됩니다.
 *
 * ---------------------------------------------------------------------------
 * 작성자: 리성림 <chenglin@smileserv.com>
 * 작성일: 2018년 04월 01일
 * ===========================================================================
 */

class Authentication
{
	/**
	 * @var string accesskey ID
	 */
	private static $accesskeyId ;
	/**
	 * @var string accesskey 비번
	 */
	private static $accesskeySecret ;
	/**
	 * @var string API서버 도메인
	 */
	private static $apiDomain ;
	/**
	 * @var string Token 요청주소
	 */
	public static $authenticationtUrl ;
	/**
	 * @var string 파일 관련 요청주소 ( 업로드 , 검색 , 수정 , 삭제 )
	 */
	public static $filesUrl ;
	/**
	 * @var string 폴더관련 요청주소 ( 생성 , 검색 )
	 */
	public static $foldersUrl ;
	/**
	 * @var string 스토리지키
	 */
	public $storageKey ;
	/**
	 * @var string 폴더키
	 */
	public $folderKey ;
	/**
	 * @var boolean 토큰 유효기간 초과할때 다시 토큰을 요청했는지
	 */
	private $countOvertime ;
	/**
	 * @var string Token
	 */
	public $token ;


	/**
	 * 클래스 생성자
	 * class 기준정보를 /inc/setting.php 파일에서 세팅한다
	 * @param array $setting 기준정보 ( accesskeyId : accesskey ID; accesskeySecret : accesskey 비번; apiDomain : API서버 도메인; storageKey : 스토리지키; folderKey : 폴더키; )
	 */
	function __construct ( $setting )
	{
		self::$accesskeyId = $setting['accesskeyId'] ;
		self::$accesskeySecret = $setting['accesskeySecret'] ;
		$domain = $setting['apiDomain'] ;
		if ( ! preg_match ( "/\/$/" , $domain ) )
			$domain .= '/' ;
		self::$apiDomain = $domain . $setting['version'] . '/' ;
		$this -> storageKey = $setting['storageKey'] ;
		$this -> folderKey = $setting['folderKey'] ;
		self::$authenticationtUrl = self::$apiDomain . 'authorization' . '/' ;
		self::$filesUrl = self::$apiDomain . 'files' . '/' ;
		self::$foldersUrl = self::$apiDomain . 'folders' . '/' ;
		$this -> countOvertime = FALSE ;
	}


	/**
	 * curl 방식으로 api 서버 접근하기
	 * @param string $url api주소
	 * @param array $headers 헤더정보
	 * @param string $action HTTP ACTION ( GET , POST , PUT , DELETE )
	 * @return object return정보
	 */
	public static function curl ( $url , $headers , $action , $postData = array () )
	{
		$curl = curl_init () ;
		curl_setopt ( $curl , CURLOPT_URL , $url ) ;
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, TRUE ) ;
		curl_setopt ( $curl , CURLOPT_HTTPHEADER , $headers ) ;
		curl_setopt ( $curl , CURLOPT_RETURNTRANSFER , true ) ;
		curl_setopt ( $curl , CURLOPT_BINARYTRANSFER , true ) ;
		curl_setopt ( $curl , CURLOPT_REFERER , $_SERVER['SERVER_NAME'] ) ; //client 서버 도메인
		if ( $action == 'POST' )
		{
			$filesend = false ;
			foreach ( $postData as $v )
				if ( get_class($v) == 'CURLFile' )
					$filesend = true;

			if ( ! $filesend )
				$postData = http_build_query($postData);
			curl_setopt ( $curl , CURLOPT_POST , 1 ) ;
			curl_setopt ( $curl , CURLOPT_POSTFIELDS , $postData ) ;
		}
		else
			curl_setopt ( $curl , CURLOPT_CUSTOMREQUEST , $action ) ;
		$re = curl_exec ( $curl ) ;
		curl_close ( $curl ) ;
		return $re ;
	}


	/**
	 * 인증토큰 요청
	 * @return array 인증토큰 ( RequestID : 요청번호 ; Token : 인증토큰 ; Result : 결과 메시지 )
	 */
	public function getToken ()
	{
		$accesskeySecret = password_hash ( self::$accesskeySecret , PASSWORD_DEFAULT ) ;
		$headers[] = 'AccesskeyID:' . self::$accesskeyId ;
		$headers[] = "AccesskeySecret:{$accesskeySecret}" ;
		$headers[] = 'RemoteAddr:' . $_SERVER['REMOTE_ADDR'] ;
		$reqToken = self::curl ( self::$authenticationtUrl , $headers , 'GET' ) ;
		if ( ! empty ( $reqToken ) )
		{
			$reqToken = json_decode ( $reqToken ) ;
			if ( isset ( $reqToken -> Token ) )
			{
				$this -> token = $reqToken -> Token ;
				return $reqToken -> Token ;
			}
			else
				echo "<script>alert('인증 토큰 생성시 오류가 발생했습니다.');</script>" ;
		}
		else
			echo "<script>alert('인증 토큰 생성시 오류가 발생했습니다.');</script>" ;
	}


	/*
	 * 파일 업로드
	 * @param string $file 파일 경로
	 * @param string $folderKey 파일이 업로드될 폴더 키
	 * @param string $token 토큰
	 * @return array 파일 전송 결과
	 */
	public function upload ( $file , $folderKey = '' , $token = '' )
	{
		$data = array();
		if ( is_array ( $file ) )
		{
			foreach ( $file as $k => $v )
				if ( is_file ( $v ) )
				{
					$name = explode ( '/' , $v ) ;
					$name = array_pop ( $name ) ;
					$data['files['.$k.']'] = curl_file_create ( $v , mime_content_type ( $v ) , base64_encode ( urlencode ( $name ) ) ) ;
				}
		}
		else
		{
			if ( is_file ( $file ) )
			{
				$name = explode ( '/' , $file ) ;
				$name = array_pop ( $name ) ;
				$data['files[0]'] = curl_file_create( $file , mime_content_type ( $file ) , base64_encode ( urlencode ( $name ) ) ) ;
			}
		}

		if ( empty ( $data ) )
			return 'No file';

		$_folderKey = $folderKey ? $folderKey : ($this -> folderKey ? $this -> folderKey : '' ) ;
		if ( empty ( $_folderKey ) )
			return 'No folderKey';

		$_token = $token ? $token : ($this -> token ? $this -> token : '' ) ;
		if ( ! $_token )
			return 'No token' ;

		$key = $folderKey ? $folderKey : ($this -> folderKey ? $this -> folderKey : NULL ) ;
		if ( ! $key )
			return 'No token key' ;

		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . $_folderKey , $headers , 'POST' , $data ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ ) ; // $re -> files
	}


	/**
	 * 파일 리스트 검색
	 * @param string $token 인증토큰
	 * @param string $folderKey 폴더키
	 * @return array 파일 정보list ( RequestID : 요청번호 ; Files : 파일 list ; Result : 결과 메시지 )
	 */
	public function filesListSelect ( $token = '' , $folderKey = '' )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : '' ) ;
		if ( ! $_token )
			return 'No token' ;

		$key = $folderKey ? $folderKey : ($this -> folderKey ? $this -> folderKey : NULL ) ;
		if ( ! $key )
			return 'No token key' ;

		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . $key . '?keyType=list' , $headers , 'GET' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ ) ; // $re -> files
	}


	/**
	 * 파일 상세 검색
	 * @param string $token 인증토큰
	 * @param string $filesKey 파일 키
	 * @return array 파일 정보 ( RequestID : 요청번호 ; Files : 파일 정보 ; Result : 결과 메시지 )
	 */
	public function filesSelect ( $token = '' , $filesKey )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : NULL ) ;
		if ( ! $_token )
			return 'No token' ;

		if ( ! $filesKey )
			return 'No files key' ;

		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . $filesKey . '?keyType=single' , $headers , 'GET' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ , $filesKey ) ; // $re -> files
	}


	/**
	 * 목록생성
	 * @param string $token 인증토큰
	 * @param string $folderKey 폴더키
	 * @param string $folderName 폴더 이름
	 * @return array 폴더키 ( RequestID : 요청번호 ; FolderKey : 폴더키 ; Result : 결과 메시지 )
	 */
	public function folderCreate ( $token = '' , $folderKey = '' , $folderName )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : '' ) ;
		if ( ! $_token )
			return 'No token' ;

		$key = $folderKey ? $folderKey : ($this -> folderKey ? $this -> folderKey : '' ) ;
		if ( ! $key )
			return 'No token key' ;

		if ( ! $folderName )
			return 'No folder name' ;

		$headers[] = 'Authorization:' . $_token ;
		$postData = array () ;
		$postData['folderName'] = $folderName ;
		$re = self::curl ( self::$foldersUrl . $key , $headers , 'POST' , $postData ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ ) ; // $re -> folders
	}


	/**
	 * 목록 조회
	 * @param string $token 인증토큰
	 * @param string $action list검색 ( list )
	 * @param string $folderKey 폴더키 ( list검색 경우에 상위 폴더키  )
	 * @return array 목록 정보 list ( RequestID : 요청번호 ; Folders : 목록 정보 list ; Result : 결과 메시지 )
	 */
	public function foldersSelect ( $token = '' , $action = '' , $folderKey = '' )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : '' ) ;
		if ( ! $_token )
			return 'No token' ;

		$key = $folderKey ? $folderKey : ($this -> folderKey ? $this -> folderKey : NULL ) ;
		if ( ! $key )
			return 'No folder key' ;

		$headers[] = 'Authorization:' . $_token ;
		if ( $action == 'list' )
			$re = self::curl ( self::$foldersUrl . $key . '?action=' . $action , $headers , 'GET' ) ;
		else
			$re = self::curl ( self::$foldersUrl . $key , $headers , 'GET' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ ) ; // $re -> folders
	}


	/**
	 * 파일 이름 수정
	 * @param string $token 인증토큰
	 * @param  string $filesName 파일 키
	 * @param  string $filesName 파일 이름
	 * @return array 결과 정보 ( RequestID : 요청번호 ; Result : 결과 메시지 )
	 */
	public function filesNameUpdate ( $token = '' , $filesKey , $filesName )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : NULL ) ;
		if ( ! $_token )
			return 'No token' ;

		if ( ! $filesKey )
			return 'No files key' ;

		if ( ! $filesName )
			return 'No files name' ;

		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . $filesKey . '?action=name&filesName=' . urlencode ( $filesName ) , $headers , 'PUT' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ , $filesKey , $filesName ) ;
	}


	/**
	 * 파일 삭제 ( 멀티 )
	 * @param string $token 인증토큰
	 * @param array $filesKeys 파일 키 ( 멀티 )
	 * @return array 결과 정보 ( RequestID : 요청번호 ; Result : 결과 메시지 )
	 */
	public function filesDelete ( $token = '' , $filesKeys )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : '' ) ;
		if ( ! $_token )
			return 'No token' ;
		if ( ! $filesKeys )
			return 'No files key' ;
		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . json_encode ( $filesKeys ) , $headers , 'DELETE' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ , $filesKeys ) ;
	}


	/**
	 * 태그 수정
	 * @param string $token 인증토큰
	 * @param  string $filesKey 파일 키
	 * @param  string $tag 태그내용
	 * @return array 결과 정보 ( RequestID : 요청번호 ; Result : 결과 메시지 )
	 */
	public function tagUpdate ( $token = '' , $filesKey , $tag = '' )
	{
		$_token = $token ? $token : ($this -> token ? $this -> token : NULL ) ;
		if ( ! $_token )
			return 'No token' ;

		if ( ! $filesKey )
			return 'No files key' ;

		$headers[] = 'Authorization:' . $_token ;
		$re = self::curl ( self::$filesUrl . $filesKey . '?action=tag&tag=' . urlencode ( $tag ) , $headers , 'PUT' ) ;
		return $this -> returnMsg ( $re , __FUNCTION__ , $filesKey , $tag ) ;
	}


	/**
	 * 결과 처리
	 * @param array 결과정보
	 * @param string $functionName 호출function 이름
	 * @param string $param1 파라미터
	 * @param string $param2 파라미터
	 * @return array 티코드 된 결과정보
	 */
	public function returnMsg ( $response , $functionName , $param1 = '' , $param2 = '' )
	{
		if ( ! $response )
			return FALSE ;
		$response = json_decode ( $response ) ;
		if ( ! isset ( $response -> Result ) )
			return FALSE ;
		if ( $this -> overtime ( $response -> Result ) )
		{
			$param = array () ;
			if ( $param1 )
				array_push ( $param , $param1 ) ;
			if ( $param2 )
				array_push ( $param , $param2 ) ;
			/**
			 * 세 토큰로 다시 function 호출하기
			 */
			return $this -> countOvertime ? NULL : call_user_func_array ( array ( $this , $functionName ) , $param ) ;
		}
		return $response ;
	}


	/**
	 *  토큰 유효시간 초과할때 다시요청 ( 한번만 )
	 * @param string $msg API return 메시지
	 * @return bool TRUE:다시요청 완료 ; FALSE:거절 ( 이미 다시요청했음 )
	 */
	public function overtime ( $msg )
	{
		if ( $msg == 'Expired token' )
		{
			$reqToken = json_decode ( $this -> getToken () ) ;
			$this -> countOvertime = TRUE ;
			return TRUE ;
		}
		else
		{
			$this -> countOvertime = FALSE ;
			return FALSE ;
		}
	}


}
