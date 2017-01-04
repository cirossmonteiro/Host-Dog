<html><head><title>
Host-Dog
</title></head><body>
<?php
	$error = array(0=>"No error.", 1001=>"Bad name for folder.");
	/*
	Error codes - _SESSION['error']:
		0: no error in _SESSION
		1001: bad name for folder
		*/
	require_once __DIR__ . '/php-graph-sdk-5.0.0/src/Facebook/autoload.php';

	session_start();
	
	if (array_key_exists('error', $_SESSION)) {
		echo $error[$_SESSION['error']];
		unset($_SESSION['error']);
	}
	
	if (!array_key_exists('user',$_SESSION)) { // login pelo Facebook nunca foi realizado
		$temp = array('app_id' => '1629326227361068',
		'app_secret' => 'aa2754025183a085b5941dcacec9ffc0', // tanto app_id quanto o app_secret são fornecidos pelo Facebook
		'default_graph_version' => 'v2.4',
		'default_access_token' => isset($_SESSION['facebook_access_token']) ? $_SESSION['facebook_access_token'] : '1629326227361068|aa2754025183a085b5941dcacec9ffc0');

		$fb = new Facebook\Facebook($temp);

		try {
			$response = $fb->get('/me?fields=id,name,locale,email, location');
			$_SESSION['user'] = $response->getGraphUser();
			exit;
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  //echo 'Graph returned an error: ' . $e->getMessage();
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  //echo 'Facebook SDK returned an error: ' . $e->getMessage();
		}
		$helper = $fb->getRedirectLoginHelper();
		$permissions = array('email', 'user_likes');
		$loginUrl = $helper->getLoginUrl('http://93.188.167.143/login-callback.php', $permissions);
		echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
	}
	
	elseif (array_key_exists('uploaded',$_SESSION['user']) && // caso o upload ja tenha sido realizado e o arquivo temporário exista
		array_key_exists('name',$_FILES['userfile'])) {
		
		function aux2($ch) { // verifica se o caracter é válido
			if (48 <= ord($ch) && ord($ch) <= 59) // 0-9
				return 1;
			if (65 <= ord($ch) && ord($ch) <= 90) // A-Z
				return 1;
			if (97 <= ord($ch) && ord($ch) <= 122) // a-z
				return 1;
			return 0;
		}
		
		function aux($foldername) { // verificar se o nome dado ao folder é válido
			$l = strlen($foldername);
			for ($i = 0; $i < $l; $i++) {
				if (!aux2($foldername[$i]))
					return 0;
			}
		}
		
		if (!aux($_POST["folder"])) { // mensagem de erro e anula o upload por conta de nome inválido para folder
			$_SESSION['error'] = 1001;
			unset($_FILES);
			header("location:index.php");
		}
		
		$temp = $_SESSION['user'];
		$conn = new PDO("mysql:host=localhost;dbname=host_dog", "root", "codigo64");
		$sql = "select * from user where id_user = ".$temp["id"]; // procura usuario
		if ($conn->exec($sql)->num_rows) // caso o usuario ja esteja cadastrado, atualiza o nome do folder
			$sql = "update user set foldername = ".$_POST["folder"]." where id_user = ".$temp["id"];
		else { // cadastrando o usuario e insere um nome para o folder
			$temp = $_SESSION['user'];
			// new user - gotta add him!!!!
			$sql = "insert into user (id_user, name, first_name, last_name, locale, location, folder, email) values (".
				$temp['id'].", ".$temp['name'].", ".$temp['first_name'].", ".$temp['last_name'].", ".
				$temp['locale'].", ".$temp['location'].", ".$_POST['folder'].", ".$temp['email'].")";
			$conn->exec($sql);
		}
		$sql = "insert into upload (id_user, filename, filesize, instant) values (". // armazena o upload novo
			$temp["id"].", '".$_FILES["fileToUpload"]["name"]."', ".$_FILES["fileToUpload"]["size"].", now())";
		unset($temp);
		$conn->exec($sql);
		$conn=null;
		mkdir($_POST["folder"]); // cria o folder, ja com um nome valido
		$target_dir = $_POST['folder'].'/';
		$target_file = $target_dir.basename($_POST['folder'].".zip");
		$uploadOk = 1;
		$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
		move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file); // coloca o arquivo no folder do usuario, no servidor
		chdir($_POST['folder']);
		exec("unzip ".$_POST['folder'].".zip"); // descompacta o .zip
		exec("rm -f ".$_POST['folder'].".zip"); // apaga o .zip
		exec("python /home/codes/after_upload.py ".$_POST['folder']." 2>&1",$output); // executa o script que analisa o folder, eliminando scripts e corrigindo nomes
		header("location:".$_POST['folder']); // redireciona para o folder ja instalado e verificado
	}
	
	elseif(array_key_exists('user',$_SESSION)) { // form para fazer o upload do arquivo
		$conn = new PDO("mysql:host=localhost;dbname=host_dog", "root", "codigo64");
		$sql = "insert into login (id_user, instant) values (".$_SESSION['user']['id'].", now())"; // registra o login realizado
		$conn->exec($sql);
		$conn=null;
		echo "
			Please, don't upload any file inside the .zip with any script or it'll be automatically erased.<br>
			<form action=\"index.php\" method=\"post\" enctype=\"multipart/form-data\">
			Select an .zip file to upload:
			<input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\"><br>
			Name the folder - only a-z, A-Z, 0-9, max 20 characters:
			<input type=\"text\" name=\"folder\"><br>
			<input type=\"submit\" value=\"Upload\" name=\"submit\">
			</form>
		";
		$_SESSION['uploaded'] = true;
	}
	
?>
</body></html>