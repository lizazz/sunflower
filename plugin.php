<?php
switch($modx->event->name){
	//silent download of user's page
		
	case "OnPageNotFound":
		//А если есть - работаем дальше
		$request = $modx->db->escape($_REQUEST['q']);
		$tmp = explode('/',$request);
		$user = array_pop($tmp);
			//Если очищенное имя не равно запрошенному - то можно отредиректить юзера
			//Также возможен вариант с косой в конце бенда - его тоже учитываем
			//сеошники должны оценить
			$user_id = $modx->db->getValue($modx->db->query(
				"SELECT id 
					FROM `modx_web_users`
					WHERE username = '".$user."' OR id = '".$user."'"));
			if(isset($user_id)){
				$_GET['user_id'] = $user_id;
				$modx->sendForward(4);	
			}
	break;		
		
	case 'OnLoadWebDocument': 
		if (is_array($_SESSION['webuser']))
			foreach($_SESSION['webuser'] as $key => $value)
				$modx->documentObject['user_'.$key] = $value;
			
		//show big photo in modal window with details
		if(0 < $_GET['bigphoto']){
			$photo = $photos = $modx->db->getRow($modx->db->query(
				"SELECT *,
					(SELECT sender
						FROM `modx_a_content`
						WHERE id = t.content_id)
						AS author_id
					FROM modx_a_message t
					WHERE 
						content_id = '".(int)$_GET['bigphoto']."'"
										  ));
			$photo_path = "/assets/images/".$photo['author_id']."/".$photo['album_id']."/".(int)$_GET['bigphoto'];
			//seach neighbors image for current photo for creating link to them
			$neighbors = $modx->runSnippet("snippet",array("get" => "give_neighbors", "author_id" => $photo['author_id'], "album_id" => $photo['album_id'], "image" => (int)$_GET['bigphoto'])); 
			$sender['avatar'] = $modx->runSnippet("snippet", array("get" => "show_avatar", "avatar" =>$photo['author_id']));
			$sender['name'] = $modx->runSnippet("snippet",array("get" => "author_name",
															  "author_id" => $photo['author_id']
															 ) 
											 );
			//forming list of friends which areon photo
			$modx->documentObject['friends_on_photo'] = $modx->runSnippet("snippet", array("get" => "show_friends_on_photo", "photo_id" => (int)$_GET['bigphoto']));
			//forming list of user's friend for marking on photo
			$modx->documentObject['friends_for_photo'] = $modx->runSnippet("snippet", array("get" => "show_friends_for_photo"));
				//forming placeholders for photopage
			$modx->documentObject = array_merge($modx->documentObject, $photo);
			$modx->documentObject = array_merge($modx->documentObject, $neighbors);
			$modx->documentObject['user_photo_id'] = $photo_path;
			$modx->documentObject['base_page'] = $modx->db->escape($_GET['basepage']);
			$modx->documentObject['author_name'] = $sender['name'];
			$modx->documentObject['author_avatar'] = $sender['avatar'];
		}
		
		// Forming user data for his page
		if($_GET['user_id'] >0){
			$userdata = $modx->db->getRow($modx->db->query("
				SELECT *, 
						(SELECT username
							FROM modx_web_users
							WHERE id = '".$modx->db->escape($_GET['user_id'])."')
					AS username
					FROM `modx_web_user_attributes`
					WHERE internalKey ='".$modx->db->escape($_GET['user_id'])."'"));
			$userdata['webAvatar'] = $modx->runSnippet("snippet", array("get" => "show_avatar", "avatar" => $modx->db->escape($_GET['user_id'])));
			foreach($userdata as $key => $value)
				$modx->documentObject['person_'.$key] = $value;
		}
	break;
	
	case "OnWebPageInit":
		if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			switch ($_REQUEST['ajax']) {
				
				//show form for sending chat messages
				case "show_message_form":
					$res = $modx->parseChunk("chat_form", array("user_id" => (int)$_POST['user_id']));
				break;
				
				//save chat-message to db
				case "send_message":
					$modx->db->query("INSERT INTO `modx_a_chat`
							SET user_sender = '".$_SESSION['webuser']['internalKey']."',
								user_recipient = '".(int)$_POST['user_id']."',
								text = '".$modx->db->escape($_POST['text'])."',
								status = '1'");
					if($_SESSION['webuser']["fullname"] == "")
							if($_SESSION['webuser']["username"] == "")
								$_SESSION['webuser']["fullname"] = "Аноним";
						else 
							$_SESSION['webuser']["fullname"] = $_SESSION['webuser']["username"];
					$res = $modx->parseChunk("tpl_comment_wall", array("sender_avatar" => $_SESSION['webuser']['internalKey'], "message" => $modx->db->escape($_POST['text']), "time_comment" => "", "sender_name" => $_SESSION['webuser']['fullname'], "delete" => ""));
				break;
				
				//show dialog between users
				case "show_messages":
					$mesages = $modx->db->makeArray($modx->db->query(
						"SELECT *,
							(SELECT username
								FROM `modx_web_users`
								WHERE id = t.user_sender)
							AS username,
							(SELECT fullname
								FROM `modx_web_user_attributes`
								WHERE internalKey = t.user_sender)
							AS fullname
							FROM `modx_a_chat` t
							WHERE 
								(user_sender = '".(int)$_POST['user_id']."'
								AND user_recipient = '".$_SESSION['webuser']['internalKey']."')
							OR
								(user_sender = '".$_SESSION['webuser']['internalKey']."'
								AND user_recipient = '".(int)$_POST['user_id']."')
							ORDER BY pub_time ASC
								"));
					//mark all messages as read
					$modx->db->query(
						"UPDATE `modx_a_chat`
							SET status = '2'
							WHERE user_recipient = '".$_SESSION['webuser']['internalKey']."'");
					foreach ($mesages as $message){
						if($message["fullname"] == "")
								if($message["username"] == "")
									$message["fullname"] = "Аноним";
							else 
								$message["fullname"] = $message["username"];
						$time = $modx->runSnippet("snippet",array("get" => "convert_time",
																  "pubtime" => $message['pub_time']
																 )); 
						$message['user_sender'] = $modx->runSnippet("snippet", array("get" => "show_avatar", "avatar" => $message['user_sender']));
						$chat .= $modx->parseChunk("tpl_comment_wall", array("sender_avatar" => $message['user_sender'], "message" => $message['text'], "time_comment" => $time, "sender_name" => $message['fullname'], "delete" => "")); 
					}
					$res = $modx->parseChunk("chat",array("chat" => $chat, "user_id" => (int)$_POST['user_id']));
				break;
				
				// delete mark of user on photo
				case "del_user_from_photo":
					$modx->db->query(
						"DELETE FROM `modx_a_photos_people`
							WHERE content_id = '".(int)$_POST['photo_id']."'
							AND
								people_id = '".(int)$_POST['user_id']."'");
				break;
				
				//add friend to photo
				case "add_friend_to_photo":
					$modx->db->query(
						"INSERT INTO `modx_a_photos_people`
							SET content_id = '".(int)$_POST['photo_id']."',
								people_id = '".(int)$_POST['user_id']."'");
					$res = $modx->runSnippet("snippet", array("get" => "show_friends_on_photo", "photo_id" => (int)$_POST['photo_id']));
				break;
				
				//delete request friendship
				case "cancel_request_friendship":
					$modx->db->query(
						"DELETE 
							FROM `modx_a_users_status`
										WHERE 
											user_inviter = '".$_SESSION['webuser']['internalKey']."'
											AND
											user_invited = '".(int)$_POST['user_id']."'");
				break;
				
				//delete data about relationship between users
				case "cancel_friendship":
					$modx->db->query(
						"DELETE 
							FROM `modx_a_users_status`
										WHERE 
											(user_inviter = '".(int)$_POST['user_id']."'
											AND
											user_invited = '".$_SESSION['webuser']['internalKey']."')
										OR (user_inviter = '".$_SESSION['webuser']['internalKey']."'
											AND
											user_invited = '".(int)$_POST['user_id']."')");
				break;
				
				//change value of friend's status from request to followers
				case "followers":
					$modx->db->query("Update `modx_a_users_status`
										SET
											stat_val = '3'
										WHERE 
											user_inviter = '".(int)$_POST['user_id']."'
											AND
											user_invited = '".$_SESSION['webuser']['internalKey']."'");
					$a = $modx->db->getValue($modx->db->query(
						"SELECT stat_val_id
							FROM `modx_a_users_status`
							WHERE
								user_inviter = '".$_SESSION['webuser']['internalKey']."'
								AND
								user_invited = '".(int)$_POST['user_id']."'"));
					if($a != "")
						$modx->db->query("Update `modx_a_users_status`
											SET
												stat_val = '4'
											WHERE 
											stat_val_id = '".$a."'");
					else
						$modx->db->query("INSERT INTO `modx_a_users_status` SET 
														user_invited = '".(int)$_POST['user_id']."',
														user_inviter = '".$_SESSION['webuser']['internalKey']."',
														stat_val = '4'");
				break;
				
				//change value of friend's status from request to confirm
				case "confirm_friend":
					$modx->db->query("Update `modx_a_users_status`
										SET
											stat_val = '2'
										WHERE 
											user_inviter = '".(int)$_POST['user_id']."'
											AND
											user_invited = '".$_SESSION['webuser']['internalKey']."'");
				// check is exist second entry? if yes - change it
					$second = $modx->db->getValue(
						"SELECT stat_val_id
							FROM `modx_a_users_status`
							WHERE 
								user_inviter = '".$_SESSION['webuser']['internalKey']."'
								AND user_invited = '".(int)$_POST['user_id']."'");
					 
					if($second != "")
						$modx->db->query("Update `modx_a_users_status`
										SET
											stat_val = '2'
										WHERE
											stat_val_id = '".$second."'");
					else
						$modx->db->query("INSERT INTO `modx_a_users_status`
											SET
												user_inviter = '".$_SESSION['webuser']['internalKey']."',
												user_invited = '".(int)$_POST['user_id']."',
												stat_val = '2'");
				break;
				
				//add request to friends to database
				case "add_friend":
					$modx->db->query("INSERT INTO `modx_a_users_status` SET 
														user_invited = '".(int)$_POST['user_id']."',
														user_inviter = '".$_SESSION['webuser']['internalKey']."',
														stat_val = '1'");	
				break;
				
				// recieve id of content and destroy all information about it
				case "del_content":
					if($_POST['content'] != ""){
						$images = $modx->db->makeArray($modx->db->query(
							"SELECT album_id,
								(SELECT sender
									FROM 
										`modx_a_content` 
									WHERE id = t.content_id) 
									AS `author_id`
								FROM `modx_a_message` t
								WHERE 
									content_id = '".(int)$_POST['content']."'
								AND type_content = '3'"));
						foreach($images as $image){
								if(is_array($image))
									if(file_exists(MODX_BASE_PATH."assets/images/".$image['author_id']."/".$image['album_id']."/".(int)$_POST['content']))
										unlink(MODX_BASE_PATH."assets/images/".$image['author_id']."/".$image['album_id']."/".(int)$_POST['content']);
						}
						$modx->db->query(
							"DELETE 
								FROM `modx_a_content` 
								WHERE
									id = '".(int)$_POST['content']."'");
						$modx->db->query(
							"DELETE 
								FROM `modx_a_comments`
								WHERE 
									content_id = '".(int)$_POST['content']."'");
						$modx->db->query(
							"DELETE 
								FROM `modx_a_message`
								WHERE 
									content_id = '".(int)$_POST['content']."'");
						$modx->db->query(
							"DELETE 
								FROM `modx_a_photos_people` 
								WHERE 
									id = '".(int)$_POST['content']."'");
						$res = false;
					}
				break;
				
				// recieve id of comment and delete it
				case "del_comment":
					if($_POST['content'] != ""){
						$modx->db->query("DELETE FROM `modx_a_comments` WHERE id = '".(int)$_POST['content']."'");
						$res = false;
					}
				break;
				
				// recieve comment by ajax and save to DB
				case "comment":
					$modx->db->query("INSERT INTO modx_a_comments SET 
														content_id = '".(int)$_POST['contentid']."',
														sender = '".$_SESSION['webuser']['internalKey']."',
														comment_text = '".$modx->db->escape($_POST['comment_text'])."'");
					$id = $modx->db->getInsertId();
					$comment = array("sender" => $_SESSION['webuser']['internalKey'],
									"pub_date" => "Только что",
									"id" => $id,
									"comment_text" => $modx->db->escape($_POST['comment_text']),
									 "delete_comment" => ""
									);
					$res = $modx->runSnippet("snippet",array("get" => "one_comment",
															"tpl" => "tpl_comment_wall",
														  	"tpl_delete" => "tpl_delete_content",
															  "comment" => $comment,
															 ) 
											 );
											 
				break;
				
				// delete photo from album by ajax
				case "del_foto":
					if ((int)$_POST['foto'] > 0){
						$image = $modx->db->getRow($modx->db->query(
							"SELECT album_id,
								(SELECT sender
									FROM 
										`modx_a_content` 
									WHERE id = t.content_id) 
									AS `author_id`
								FROM `modx_a_message` t
								WHERE 
									content_id = '".(int)$_POST['foto']."'
								AND type_content = '3'"));
						$modx->db->query(
							"DELETE 
								FROM `modx_a_content` 
								WHERE
									id = '".(int)$_POST['foto']."'"
							);
						$modx->db->query(
							"DELETE 
								FROM `modx_a_comments`
								WHERE content_id = '".(int)$_POST['foto']."'");
						$modx->db->query(
							"DELETE 
								FROM `modx_a_message`
								WHERE content_id = '".(int)$_POST['foto']."'");
						$modx->db->query(
							"DELETE 
								FROM `modx_a_photos_people`
								WHERE content_id = '".(int)$_POST['foto']."'");
								unlink(MODX_BASE_PATH."assets/images/".$image['author_id']."/".$image['album_id']."/".(int)$_POST['foto']);
						$res = false;
					}
				break;
				
				// insert avatar photo name to DB by ajax
				case "on_avatar":
					if ($_POST['foto'] > 0){
						if(file_exists(MODX_BASE_PATH."assets/images/avatars/".$_SESSION['webuser']['internalKey']))
							unlink(MODX_BASE_PATH."assets/images/avatars/".$_SESSION['webuser']['internalKey']);
						$image = $modx->db->getRow($modx->db->query(
							"SELECT album_id,
								(SELECT sender
									FROM 
										`modx_a_content` 
									WHERE id = t.content_id) 
									AS `author_id`
								FROM `modx_a_message` t
								WHERE 
									content_id = '".(int)$_POST['foto']."'
								AND type_content = '3'"));
						copy(MODX_BASE_PATH."assets/images/".$image['author_id']."/".$image['album_id']."/".(int)$_POST['foto'], MODX_BASE_PATH."assets/images/avatars/".$_SESSION['webuser']['internalKey']);
						$_SESSION['webuser']['webAvatar'] = $_SESSION['webuser']['internalKey'];
					}
				break;
				
				//delete album and all photos from it
				case "del_album":
					$id = (int)$_POST['album'];
					if ($id > 0){
						$photos_id = $modx->db->makeArray($modx->db->query(
							"SELECT content_id 
								FROM `modx_a_message` 
								WHERE
									album_id = '".$id."'"));
						foreach ($photos_id as $photo_id){
							$modx->db->query(
								"DELETE 
												FROM `modx_a_content` 
												WHERE
													id = '".$photo_id['content_id']."'"
											);
							$modx->db->query("DELETE FROM `modx_a_comments` WHERE id = '".$photo_id."'");
							$modx->db->query("DELETE FROM `modx_a_photos_people` WHERE id = '".$photo_id."'");
						}
						$modx->db->query("DELETE 
											FROM `modx_a_message` 
											WHERE
												album_id = '".$id."'"
										);
						$modx->db->query("DELETE 
											FROM `modx_a_album` 
											WHERE
												id = '".$id."'"
										);
						$path = MODX_BASE_PATH."assets/images/".$_SESSION['webuser']['internalKey']."/".$id;
						$folder = glob($path);
						if ($folder = glob($path."/*")) {
							foreach($folder as $obj) {
								unlink($obj);
							}
						}
						rmdir($path);
					}
				break;
				
				//create new album - insert to DB, create folder and etc
				case "newalbum":
					if (0 < count($_POST['albumname'])){
						$modx->db->query("INSERT INTO modx_a_album SET 
														name = '".$modx->db->escape($_POST['albumname'])."',
														author_id = '".$_SESSION['webuser']['internalKey']."'");	
						$id = $modx->db->getInsertId();
						mkdir (MODX_BASE_PATH."assets/images/".$_SESSION['webuser']['internalKey']."/".$id,0777);
						$res = $id;
					}
				break;
				
				//save message from user's wall in db
				case "message":
					$modx->db->query(
									"INSERT INTO `modx_a_content`
										SET
											sender = '".(int)$_POST['sender']."',
											recipient = '".(int)$_POST['recipient']."'"
					);
					$id = $modx->db->getInsertId();
					if ($_POST['message'] != "")
						$modx->db->query(
									"INSERT INTO `modx_a_message`
										SET
											type_content = '1',
											content_id = '".$id."',
											text = '".$modx->db->escape($_POST['message'])."'"
										);
					if($_POST['youtube'] != ""){
						preg_match("/(?:http:\/\/)?(?:www\.)?youtu(?:\.be|be\.com)\/(?:watch\?v=)?([\w\-]{6,12})(?:\&.+)?/i",$_POST['youtube'],$out);
						$modx->db->query(
									"INSERT INTO `modx_a_message`
										SET
											type_content = '2',
											content_id = '".$id."',
											text = '".$modx->db->escape($out[1])."'"
						);
					}
					$message = array("sender_avatar" => $_SESSION['webuser']['avatar'],
									"pub_date" => "Только что",
									"youtube" => $modx->db->escape($out[1]),
									"id" => $id,
									"album" => "",
									"sender" => $_SESSION['webuser']['internalKey'],
									"discription" => "",
									"sender_name" => $_SESSION['webuser']['fullname'],
									"message" => $modx->db->escape($_POST['message']));
					$res = $modx->runSnippet("snippet", array("get" => "one_content",
														   "tpl" => "tpl_wall_message",
														   "tpl_video" => "tpl_youtube",
														   "tpl_image" => "tpl_image",
														   "tpl_comment" => "tpl_comment_wall",
														   "tpl_delete" => "tpl_delete_content",
															 "tpl_comment_form" => "tpl_wall_comment_form",
														   "message" => $message ));
				break;
				
				// User's registration auth by ajax						
				case "reg":
					if($_POST['reg'] > 0) {
						$pass = md5($_POST['reg']["pass"]);
						$data = $modx->db->getValue($modx->db->query(
							"SELECT internalKey 
								FROM `modx_web_user_attributes` 
								WHERE
									email = '".$modx->db->escape($_POST['reg']["email"])."'"
						));
						if($data){
							$res = 13;
						}
						else{
							//insert username and pass to table web users
							$res = 20;
								$modx->db->query(
									"INSERT INTO modx_web_users 
										SET
											username = '".$modx->db->escape($_POST['reg']["email"])."',
											password = '".$pass."'"
								);
							//получение id пользователя после его занесение в modx_web_users
							$id = $modx->db->getInsertId();
						
							//Запись id, email и и чекбокса подписки в базу modx_web_user_attributes
							$modx->db->query(
								"INSERT INTO modx_web_user_attributes
									SET
										email = '".$modx->db->escape($_POST['reg']['email'])."', 
										internalKey = '".$id."'"
											);
							
							//запись id пользователя в webusergroup чтобы он стал авторизированным
							$modx->db->query(
								"INSERT INTO modx_web_groups 
									SET
										webgroup = '1', 
										webuser = '".$id."'"
											);		
							//авторизация пользователя после регистрации
							
							$modx->runSnippet("Auth", array("username" => $modx->db->escape($_POST['reg']['email']), "autologin" => true));
							//create user's folder for his content
							mkdir("assets/images/".$id,0777);
							$chunk = $modx->parseChunk("reg_letter",
																   array("email" => $_POST['reg']['email'], "password" => $_POST['reg']["pass"],"site" => $modx->config['site_url'])
																  );	
							$modx->runSnippet("Snippet",
								Array(
									"get" => "sendmail",
									"to" => $_POST['reg']['email'],
									"subject" => "Регистрация в социальной сети Sunflower.loc",
									"body" => $chunk
								));
						}
					} // end if post
				break;//end of reg case
				
				
				//password recover, we recieve email by ajax check it and send temporary password 
				//for user access
				case "forg":
					if (0 < count($_POST['forg'])) {
						$captcha = $modx->runSnippet("captcha", array("get"=>"check_captcha", 
																	 "capch" => $modx->db->escape($_POST['captcha'])
																	)
												   );
						$captcha = json_decode($captcha, true);
						if($captcha != true)
							$res = 14;
						else{
							$sql = 
								"SELECT internalKey 
								FROM modx_web_user_attributes 
								WHERE 
									email = '".$_POST['forg']['email']."'";
								$data = $modx->db->query($sql);
								$data = $modx->db->getValue($data);
							if(!$data)
								$res = 11;
							else{
								$res = 20;
								$time = getdate();
								$time = $_POST['forg']['email'].$time['0'];
								$cashpass = md5($time);
								$sql = 
									"UPDATE modx_web_users 
										SET cachepwd = '".$cashpass."'
										WHERE 
											id = '".$data."'";
								$data = $modx->db->query($sql);
								$chunk = $modx->parseChunk("recover_pass_letter",
																	   array("password" => $time,"md5"=>$cashpass,"site" => $modx->config['site_url'])
																	  );
								$modx->runSnippet("Snippet",
									Array(
										"get" => "sendmail",
										"to" => $_POST['forg']['email'],
										"subject" => "Восстановление пароля",
										"body" => $chunk
									));
							}
						}	
							
					};
				break;	
				
				case "change":
					//change user datum
					if(0 < count($_POST['user'])){
						$ans = 20;
						if(!empty($_POST['user']["name"])) {
							$_SESSION ["webuser"]["fullname"] = $modx->db->escape($_POST['user']["name"]);
							$string['name'] = " fullname = '".$modx->db->escape($_POST['user']["name"])."'";
						}
						if(!empty($_POST['user']["email"])) {
							$_SESSION ["webuser"]["email"] = $modx->db->escape($_POST['user']["email"]);
							$string['email'] = " email = '".$modx->db->escape($_POST['user']["email"])."'";
						}
						if(!empty($_POST['user']['city'])) {
							$_SESSION ["webuser"]["city"] = $modx->db->escape($_POST['user']['city']);
							$string['city'] = " city = '".$modx->db->escape($_POST['user']['city'])."'";
						}
						if(!empty($_POST['user']['birthday'])) {
							$_SESSION ["webuser"]["birthday"] = $modx->db->escape($_POST['user']['birthday']);
							$string['birthday'] = " birthday = '".$modx->db->escape($_POST['user']['birthday'])."'";
						}
						$password = $modx->db->escape($_POST['user']["new_password"]);
						$old_pass = md5($_POST['user']["old_password"]);
						if(empty($_POST['user']["nick"])){
							$_POST['user']["nick"] = $_SESSION['webuser']['internalKey'];
						}
							$check_nick = $modx->db->getValue($modx->db->query(
								"SELECT id 
									FROM `modx_web_users` 
									WHERE 
									username = '".$modx->db->escape($_POST['user']['nick'])."'
									OR id ='".$modx->db->escape($_POST['user']['nick'])."'"
																	   ));	
							if(isset($check_nick) AND $check_nick != $_SESSION['webuser']['internalKey'])
								$ans = 13;
							else{
								$_SESSION ["webuser"]["username"] = $modx->db->escape($_POST['user']["nick"]);
								$string2['nick'] = " username = '".$modx->db->escape($_POST['user']["nick"])."'";
							}
						
						
						// check password if it right - update, else return error
						if(!empty($password)){
							$check = $modx->db->getRow($modx->db->query(
								"SELECT password 
									FROM `modx_web_users` 
									WHERE 
										id = '".$_SESSION['webuser']['internalKey']."'"
																	   ));	
							if(($check["password"] != $old_pass) AND $check["password"] != "")
									$ans = 11;
							else
								$string2['password'] = " password = '".md5($password)."'";
							
						}
						if($string != ""){
							$string = implode(",", $string);
							$modx->db->query(
								"UPDATE modx_web_user_attributes 
									SET	".$string."
									WHERE 
										internalKey = '".$_SESSION['webuser']['internalKey']."'"
										);
						}
						//$write отвечает за то писать в БД или запрос пустой
						if($string2 != ""){
							$string2 = implode(",", $string2);
							$modx->db->query(
								"UPDATE modx_web_users
									SET ".$string2."
									WHERE 
										id = '".$_SESSION['webuser']['internalKey']."'"
											);	
						}
						$res = json_encode(array("key" => $ans, "username" => $_SESSION['webuser']['username']));
					} //end if "change user datum"	
				break;
								
				// user's logout
				case "logout":
					unset($_SESSION["webuser"]);
					unset($_SESSION["webValidated"]);
					unset($_SESSION["webDocgroups"]);
				break;
						
				
				// user auth by ajax						
				case "auth":
					if((int)$_POST['second_login'] == 1){
						$captcha = $modx->runSnippet("captcha", array("get"=>"check_captcha", 
																	 "capch" => $modx->db->escape($_POST['captcha'])
																	)
												   );
						$captcha = json_decode($captcha, true);
					}else 
						$captcha = true;
					if($captcha == true)
						$answer = $modx->runSnippet("Auth", array("username" => $modx->db->escape($_POST["email"]),
															  "password" => $modx->db->escape($_POST["pass"])
															 )
											   );
					else 
						$answer = "14";
					if($_SESSION['webuser']['username'] != "")
						$username = $_SESSION['webuser']['username'];
					else
						$username = $_SESSION['webuser']['internalKey'];
					$a = array('key' => $answer, 'username' => $username);
					$res = json_encode($a);
				break;
				
			} // end switch ajax
			echo $res;
			die (); // return result of switch ajax
		}  // end switch if
				
		
		//save fotos in album
		if (0 < count($_POST['photo'])) {
			//сохранения в базе данных
			$ddd = count($_FILES['photo']['name']['foto']);
			$album_id = (int)$_POST['photo']['id'];
			for ($i = 0; $i < $ddd; $i++){
				if($_FILES['photo']['name']['foto'][$i]){
					$modx->db->query(
						"INSERT INTO modx_a_content
							SET 
								sender = '".$_SESSION['webuser']['internalKey']."',
								recipient = '".$_SESSION['webuser']['internalKey']."'");
					$id = $modx->db->getInsertId();
					$modx->db->query(
						"INSERT INTO `modx_a_message`
							SET 
								album_id = '".$album_id."',
								text = '".$modx->db->escape($_POST['photo']['description'][$i])."',
								type_content = '3',
								content_id = '".$id."'");
						if($_FILES['order']['size']['foto'][$i] > 1024*10*1024){
						echo ("Размер файла превышает 10 мегабайт");
						exit;
					}
					//    Проверяем загружен ли файл
						if(is_uploaded_file($_FILES['photo']["tmp_name"]['foto'][$i])){
						//   Если файл загружен успешно, перемещаем его
						//    из временной директории в конечную
							$res=$_FILES['photo']["tmp_name"]['foto'][$i];
						move_uploaded_file($_FILES["photo"]["tmp_name"]['foto'][$i],MODX_BASE_PATH."assets/images/".$_SESSION['webuser']['internalKey']."/".$album_id."/".$id);
						
					} else {
						die("Ошибка загрузки файла");
					}
				}
			}			
			header("Location: ".$modx->makeUrl(10,"","album_id=".$album_id));
			die;
		} //end case save photo
	
	
	
	
		// registration and authorization by ulogin
		if(isset($_REQUEST['token'])){
			$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
			$user = json_decode($s, true);
			//$user['network'] - соц. сеть, через которую авторизовался пользователь
            //$user['identity'] - уникальная строка определяющая конкретного пользователя соц. сети
            //$user['first_name'] - имя пользователя
            //$user['last_name'] - фамилия пользователя
			//is user registred? if yes login if not registration
			if (isset($user["identity"])){
				$data = $modx->db->getRow($modx->db->query(
					"SELECT *
							FROM `modx_web_users`
							WHERE 
								id = 
									(SELECT internalKey 
										FROM `modx_web_user_attributes`
										WHERE ulogin = '".$user['identity']."')"));
				if(is_array($data)){
					$data['ulogin'] = true;
					$modx->runSnippet("auth", $data);
						header("Location: ".$modx->makeUrl(4,"","&user_id=".$data['id']));
				}else{//if user hasn't yet registred - add his data to  database
					$username = "ulogin".$user['network'].array_pop(explode('/',$user['identity']));
					$password = md5(time().$user['network'].array_pop(explode('/',$user['identity'])));
					$password = substr($password, 0, 8);
						$modx->db->query(
									"INSERT INTO modx_web_users 
										SET
											username = '".$username."', 
											password = '".md5($password)."'"
								);
							//получение id пользователя после его занесение в modx_web_users
						$id = $modx->db->getInsertId();
						
							//Запись ulogin, и других данных от полученных от сервиса ulogin в базу modx_web_user_attributes
						$modx->db->query(
								"INSERT INTO modx_web_user_attributes
									SET
										ulogin = '".$modx->db->escape($user['identity'])."', 
										fullname = '".$modx->db->escape($user['first_name'])." ".$modx->db->escape($user['last_name'])."',
										internalKey = '".$id."'"
											);
							//запись id пользователя в webusergroup чтобы он стал авторизированным
						$modx->db->query(
								"INSERT INTO modx_web_groups 
									SET
										webgroup = '1', 
										webuser = '".$id."'"
											);		
							
							//авторизация пользователя после регистрации
					$modx->runSnippet("auth", array("username" => $username, "password" => $password));
							
					//	$modx->runSnippet("snippet", array("get" => "ulogin", "id" => $id));
						//create user's folder for his content
							mkdir("assets/images/".$id,0777);
						header("Location: ".$modx->makeUrl(3));
					}	//end else for ulogin registration
				}else{
				die("Пользователя с такими данными не найдено");
			}
		}
	
	
		//change temporary password to new ones
		if (0 < count($_GET['checkpass'])) {
			$data = $modx->db->query(
				"UPDATE modx_web_users
					SET cachepwd = 'NULL',
						password='".$_GET['checkpass']."'
					WHERE 
						cachepwd = '".$_GET['checkpass']."'");
		}	
?>	
	break;	
} //end main switch