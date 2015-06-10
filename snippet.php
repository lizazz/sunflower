<?php
$res = '';
	switch($get){
		
		//show quantity of new friends request
		case "new_message":
			$count = $modx->db->getValue($modx->db->query(
				"SELECT 
					COUNT(text)
					FROM `modx_a_chat`
					WHERE user_recipient = '".$_SESSION['webuser']['internalKey']."'
					AND status = '1'"));
			if($count > 0)
				$res = $modx->parseChunk("tpl_link_friend",array("status" => "new_friend", "text" => "(+".$count.")"));
		break;
		
		//show friends which was mark on photo
		case "show_friends_on_photo":
			$friends_on_photo = $modx->db->makeArray($modx->db->query(
				"SELECT people_id,
					(SELECT fullname
						FROM `modx_web_user_attributes`
						WHERE internalKey = t.people_id)
					AS fullname,
					(SELECT username
						FROM `modx_web_users` 
						WHERE id = t.people_id)
					AS username
					FROM `modx_a_photos_people` t
					WHERE content_id = '".$photo_id."'"));
			if(isset($friends_on_photo)){
				$res .= "<small>На этом фото: ";
					foreach($friends_on_photo as $person_on_photo){
						if($person_on_photo['people_id'] == $_SESSION['webuser']['internalKey'])
							$delete =$modx->parseChunk("delete_from_photo", array("user" => $_SESSION['webuser']['internalKey']));
						else
							$delete = "";
						if($person_on_photo["fullname"] == "")
							if($person_on_photo["username"] == "")
								$person_on_photo["fullname"] = "Аноним";
						else 
							$person_on_photo["fullname"] = $person_on_photo["username"];
						$res .= $modx->parseChunk("friends_on_photo", array("friend_name" => $person_on_photo["fullname"],"friend_username" => $person_on_photo["username"], "delete" => $delete, "user_id" => $person_on_photo['people_id']));
				}
				$res .= "</small>";
			}
		break;
		
		//show friend to mark to image on
		case "show_friends_for_photo":
			$friends = $modx->db->makeArray($modx->db->query(
				"SELECT user_inviter,
					(SELECT fullname
						FROM `modx_web_user_attributes`
						WHERE internalKey = t.user_inviter)
					AS fullname,
					(SELECT username
						FROM `modx_web_users` 
						WHERE id = t.user_inviter)
					AS username
				FROM `modx_a_users_status` t
				WHERE 
					user_invited = '".$_SESSION['webuser']['internalKey']."'
					AND stat_val = '2'"));
			$res .= $modx->parseChunk("friends_for_photo", array("friend_id" => $_SESSION['webuser']['internalKey'], "friend_fullname" => "Я"));
			foreach ($friends as $friend){
				if($friend["fullname"] == "")
					if($friend["username"] == "")
						$friend["fullname"] = "Аноним";
				else 
					$friend["fullname"] = $friend["username"];
				$res .= $modx->parseChunk("friends_for_photo", array("friend_id" => $friend['user_inviter'], "friend_fullname" => $friend['fullname']));
			}
		break;
		
		//recieve id of image and return path of neighbors image 
		case "give_neighbors":
			$folder = MODX_BASE_PATH."assets/images/".$author_id."/".$album_id;
			$path = glob($folder."/*");
			$image = $folder."/".$image;
			$key = array_search($image,$path);
			if ($path[$key-1] != null){
				$sender["prev"] = explode("/",$path[$key-1]);
				$sender['prev_image'] = array_pop($sender["prev"]);
				$res ['prev_image'] = $modx->parseChunk("image_button",array("class" => "previous", "link" => "/photo?bigphoto=".$sender['prev_image']."&basepage=".$modx->db->escape($_GET['basepage']), "text" => "Предыдущая"));
			}
			else
				$res ['prev_image'] = $modx->parseChunk("image_button",array("class" => "previous disabled", "link" => "#", "text" => "<span aria-hidden='true'></span>Предыдущая"));
				
			if ($path[$key+1] != null){
				$sender["next"] = explode("/",$path[$key+1]);
				$sender['next_image'] = array_pop($sender["next"]);
				$res['next_image'] = $modx->parseChunk("image_button",array("class" => "next", "link" => "/photo?bigphoto=".$sender['next_image']."&basepage=".$modx->db->escape($_GET["basepage"]), "text" => "Следующая"));
			}
			else
				$res['next_image'] = $modx->parseChunk("image_button",array("class" => "next disabled", "link" => "#", "text" => "<span aria-hidden='true'></span>Следующая"));
		break;
		
		// forming path for returning to user's photoalbums
		case "albums_path":
			$user_id = $modx->db->getValue($modx->db->query(
				"SELECT author_id
					FROM `modx_a_album` 
					WHERE id = '".$modx->db->escape($_GET['album_id'])."'"));
			$res = "/photos?user_id=".$user_id;
		break;
		
		//show friends of user
		case "friends":
			if(isset($_GET['user_id']))
				$user = $modx->db->escape($_GET['user_id']);
			else
				$user = $_SESSION['webuser']['internalKey'];
			$friends = $modx->db->makeArray($modx->db->query(
						"SELECT user_inviter 
							FROM `modx_a_users_status`
							WHERE user_invited = '".$user."'
							AND stat_val = '2'"));
		if(!empty($friends)){
			foreach($friends as $friend){
				$people[] = $modx->db->getRow($modx->db->query(
					"SELECT username, id,
						(SELECT fullname
							FROM `modx_web_user_attributes`
							WHERE internalKey = t.id)
						AS fullname,
						(SELECT stat_val
							FROM `modx_a_users_status`
							WHERE user_inviter = t.id 
							AND 
							user_invited = '".$user."')
						AS status,
						(SELECT 
							COUNT(text)
							FROM `modx_a_chat`
							WHERE user_recipient = '".$user."'
							AND user_sender = t.id
							AND status = '1')
						AS new_message
						FROM `modx_web_users` t
						WHERE id = '".$friend["user_inviter"]."'
						ORDER BY t.id DESC
							LIMIT 30"));
					}
				foreach ($people as $person){
					if($person['id']!= $user){
						if($person['fullname']==""){
							if($person['username']==""){
								$person['fullname'] = "Аноним";
							}
							else {
								$person['fullname'] = $person['username'];
							}
							
						}
						
						$person['fullname'] = mb_substr($person['fullname'], 0, 30);
						
						if($person['new_message'] != "0")
							$person['new_message'] = "glyphicon glyphicon-comment";
						else	
							$person['new_message'] = "";
						$person['status_disc_pos'] = "Вы друзья";
						$person['status_ico_pos'] = "ok";
						$person['negative_ico'] = "remove";
						$person['negative_disc'] = "Убрать из друзей";
						$person['avatar'] = $modx->runSnippet("snippet", array("get" => "show_avatar", "avatar" => $person['id']));
						$res .= $modx->parseChunk($tpl,$person);
					}	
				}
				
			}
			else
				$res = $modx->parseChunk($tpl_no_people, array());	
		break;
		
		//change title of page depend on value of $_GET['status']
		case "people_title":
			switch ($_GET['status']){
				case "new_friend":
					$res = "Заявки в друзья";
				break;
				case "followers":
					$res = "Ваши подписчики";
				break;
				case "idols":
					$res = "Ваши кумиры";
				break;
				default:
					$res = "Люди";
				break;
			}
		break;
		
		//show quantity of new friends request
		case "new_friend":
			$count = $modx->db->getValue($modx->db->query(
				"SELECT 
					COUNT(stat_val)
					FROM `modx_a_users_status`
					WHERE user_invited = '".$_SESSION['webuser']['internalKey']."'
					AND stat_val = '1'"));
			if($count > 0)
				$res = $modx->parseChunk("tpl_link_friend",array("status" => "new_friend", "text" => "(+".$count.")"));
		break;
		
		//check user and show/hide button of adding new foto to album
		case "add_foto_button":
			$user = $modx->db->getValue($modx->db->query(
				"SELECT author_id
					FROM `modx_a_album`
					WHERE id = '".(int)$_GET['album_id']."'"));
		if($_SESSION['webuser']['internalKey'] == $user){
			$res = $modx->parseChunk($tpl,array());
		}
		break;
		
		//forming page with user's video
		case "video":
		// if we has $_GET we need show other user's video if not - own video collection
			if($_GET['user'] == "")
				$_GET['user'] = $_SESSION['webuser']['internalKey'];
			$showBlock = false;
				$user_content = $modx->db->makeArray($modx->db->query(
					"SELECT id, sender,
							(SELECT username
								FROM `modx_web_users`
								WHERE id = t.sender)
						AS 'sender_name'
						FROM `modx_a_content` t
						WHERE recipient = '".(int)$_GET['user']."'")); 
				foreach($user_content as $entry){
					$video = $modx->db->getRow($modx->db->query(
						"SELECT text, content_id
							FROM `modx_a_message`
							WHERE content_id = '".$entry['id']."'
							AND type_content = '2'")); 
					
					if(!empty($video)){
						$video['id'] = $entry['id'];
						if($entry['sender'] == $_SESSION['webuser']['internalKey'])
							$video['delete_content'] = $modx->parseChunk($tpl_delete, array("comment_id" => $video['id'],
																					  "del_class" => "del-content  del-button",
																					  "title" => "Удалить запись"
																		   ));
						else 
							$video['delete_content'] = "";
						$video['author_name'] = $entry['sender_name'];
						$video['author_avatar'] = $modx->runSnippet("snippet",array("get" => "show_avatar", "avatar" => $entry['sender']));
						$video['link'] = $modx->parseChunk($tpl_frame, array("youtube" => $video['text']));
						$video['comment'] = $modx->runSnippet("snippet",array("get" => "give_comment",
															 			"tpl" => $tpl_comment,
																		"tpl_delete" => $tpl_delete,
																		"comment_id" => $entry['id']																							) 
											 );
						$res .= $modx->parseChunk($tpl,$video);
						$showBlock = true;
					}
				}
			if(!$showBlock)
					$res = $modx->parseChunk($tpl2,array());
		break;
		
		//show all registred users
		case "people":
			switch ($_GET['status']){
				//show person who want add you to friend
				case "new_friend":
					$new_friends = $modx->db->makeArray($modx->db->query(
						"SELECT user_inviter 
							FROM `modx_a_users_status`
							WHERE user_invited = '".$_SESSION['webuser']['internalKey']."'
							AND stat_val = '1'"));
					foreach($new_friends as $new_friend){
						$people[] = $modx->db->getRow($modx->db->query(
							"SELECT username, id,
								(SELECT fullname
								FROM `modx_web_user_attributes`
								WHERE internalKey = t.id)
								AS fullname,
								(SELECT stat_val
									FROM `modx_a_users_status`
									WHERE user_inviter = t.id 
									AND 
									user_invited = '".$_SESSION['webuser']['internalKey']."')
								AS status
								FROM `modx_web_users` t
								WHERE id = '".$new_friend["user_inviter"]."'
								ORDER BY t.id DESC
								LIMIT 30"));
					}
				break;
				
				//show user's followers
				case "followers":
					$followers = $modx->db->makeArray($modx->db->query(
						"SELECT user_inviter 
							FROM `modx_a_users_status`
							WHERE user_invited = '".$_SESSION['webuser']['internalKey']."'
							AND stat_val = '3'"));
					foreach($followers as $follower){
						$people[] = $modx->db->getRow($modx->db->query(
							"SELECT username, id,
								(SELECT fullname
								FROM `modx_web_user_attributes`
								WHERE internalKey = t.id)
								AS fullname,
								(SELECT stat_val
									FROM `modx_a_users_status`
									WHERE user_inviter = t.id 
									AND 
									user_invited = '".$_SESSION['webuser']['internalKey']."')
								AS status
								FROM `modx_web_users` t
								WHERE id = '".$follower["user_inviter"]."'
								ORDER BY t.id DESC
								LIMIT 30"));
					}
				break;
				
				//show user's idols
				case "idols":
					$idols = $modx->db->makeArray($modx->db->query(
						"SELECT user_inviter 
							FROM `modx_a_users_status`
							WHERE user_invited = '".$_SESSION['webuser']['internalKey']."'
							AND stat_val = '4'"));
					foreach($idols as $idol){
						$people[] = $modx->db->getRow($modx->db->query(
							"SELECT username, id,
								(SELECT fullname
								FROM `modx_web_user_attributes`
								WHERE internalKey = t.id)
								AS fullname,
								(SELECT stat_val
									FROM `modx_a_users_status`
									WHERE user_inviter = t.id 
									AND 
									user_invited = '".$_SESSION['webuser']['internalKey']."')
								AS status
								FROM `modx_web_users` t
								WHERE id = '".$idol["user_inviter"]."'
								ORDER BY t.id DESC
								LIMIT 30"));
					}
				break;
				
				//if status is empty show all registred people
				default:
					$people = $modx->db->makeArray($modx->db->query(
						"SELECT username, id,
							(SELECT fullname
								FROM `modx_web_user_attributes`
								WHERE internalKey = t.id)
						AS fullname,
							(SELECT stat_val
									FROM `modx_a_users_status`
									WHERE user_inviter = t.id 
									AND 
									user_invited = '".$_SESSION['webuser']['internalKey']."')
						AS status,
							(SELECT stat_val
									FROM `modx_a_users_status`
									WHERE user_inviter = '".$_SESSION['webuser']['internalKey']."'
									AND 
									user_invited = t.id)
								AS backstatus
						FROM `modx_web_users` t
						ORDER BY t.id DESC
						LIMIT 30
						"));
				break;
			}
			if(!empty($people)){
				foreach ($people as $person){
					if($person['id']!= $_SESSION['webuser']['internalKey']){
						if($person['fullname']==""){
							if($person['username']==""){
								$person['fullname'] = "Аноним";
							}
							else {
								$person['fullname'] = $person['username'];
							}
							
						}
						if(isset($_SESSION['webuser']['internalKey'])){
							switch ($person['status']){
								//if status =1 person wait confirming to friends
								case "1":
									$person['status_disc_pos'] = "Подтвердить заявку в друзья";
									$person['status_ico_pos'] = "check";
									$person['negative_ico'] = "minus";
									$person['negative_disc'] = "Оставить в подписчиках";
								break;
								
								// status=2 you and person are friend
								case "2":
									$person['status_disc_pos'] = "Вы друзья";
									$person['status_ico_pos'] = "ok";
									$person['negative_ico'] = "remove";
									$person['negative_disc'] = "Убрать из друзей";
								break;
								
								// status=3 person is your follower
								case "3":
									$person['status_disc_pos'] = "Ваш поклонник. Добавить в друзья";
									$person['status_ico_pos'] = "eye-open";
									$person['negative_ico'] = "remove";
									$person['negative_disc'] = "Убрать из поклонников";
								break;
								
								//person is your idol
									case "4":
										$person['status_disc_pos'] = "вы в подписчиках";
										$person['status_ico_pos'] = "star";
										$person['negative_ico'] = "remove";
										$person['negative_disc'] = "Отписаться";
									break;
								default:
								switch ($person['backstatus']){
									// if $person['status'] is not defined and $person['backstatus'] is 1
									// it mean that you sent request to friend
									case "1":
										$person['status_disc_pos'] = "Отправлен запрос в друзья";
										$person['status_ico_pos'] = "envelope";
										$person['negative_ico'] = "remove-circle";
										$person['negative_disc'] = "Отозвать запрос в друзья";
									break;
									// if $person['status'] and $person['backstatus'] is empty you haven't any relationship 
									default:
										$person['status_disc_pos'] = "Добавить в друзья";
										$person['status_ico_pos'] = "plus";
										$person['negative_ico'] = "";
										$person['negative_disc'] = "";
									break;
								}
								break;
							}//end switch
						} //end if
						else{
							$person['status_disc_pos'] = "";
										$person['status_ico_pos'] = "";
										$person['negative_ico'] = "";
										$person['negative_disc'] = "";
						}
						$person['avatar'] = $modx->runSnippet("snippet", array("get" => "show_avatar", "avatar" => $person['id']));
						$res .= $modx->parseChunk($tpl,$person);
					}	
				}
				
			}
			else
				$res = $modx->parseChunk($tpl_no_people, array());
		break;
		
		//insert comment to current content
		case "give_comment":
		// read all comments for current entry from DB
			$comments = $modx->db->makeArray($modx->db->query(
				"SELECT *
					FROM modx_a_comments
					WHERE 
						content_id = '".$comment_id."'"
										  ));
		//each step of foreach is 1 comment for current entry
			foreach($comments as $comment){
				$res .= $modx->runSnippet("snippet",array("get" => "one_comment",
															"tpl" => $tpl,
														  	"tpl_delete" => $tpl_delete,
															  "comment" => $comment
															 ) 
											 );
			}
		break;
				
		// recieve array of comment's id and form they with detail
		case "one_comment":
			$sender['name'] = $modx->runSnippet("snippet",array("get" => "author_name",
															  "author_id" => $comment['sender']
															 ) 
											 );
			$sender["sender_avatar"] = $modx->runSnippet("snippet",array("get" => "show_avatar",
															  "avatar" => $comment['sender']
															 ) 
											 ); 
			if ($comment['pub_date'] == "Только что")
				$time_comment = $comment['pub_date'];
			else
				$time_comment = $modx->runSnippet("snippet",array("get" => "convert_time",
															  "pubtime" => $comment['pub_date']
															 ) 
											 );
			if($comment['sender'] == $_SESSION['webuser']['internalKey'])
				$comment['delete_comment'] = $modx->parseChunk($tpl_delete, array("comment_id" => $comment['id'],
																					  "del_class" => "del-comment",
																					  "title" => "Удалить комментарий"
																		   ));	
			$res = $modx->parseChunk($tpl, array("message" => $comment["comment_text"],
												 		"id" => $comment['id'],
													  "sender_name" => $sender['name'],
												 		"sender_avatar" => $sender["sender_avatar"],
													  "sender_id" => $comment['sender'],
													  "time_comment" => $time_comment,
													 "delete" => $comment['delete_comment']));
		break;
		
		//recieve id of sender and return name, image and etc
		case "convert_time":
			$stamp = strtotime($pubtime);
			$res = date('d.m.y в H:i',$stamp);
		break;
		
		//recieve id of author and return his name
		case "author_name":
			$res = $modx->db->getValue($modx->db->query(
				"SELECT 
						fullname
						FROM `modx_web_user_attributes`
						WHERE internalKey = '".$author_id."'"));
		break;
		
		//say user that his changing of data has been saved
		case "success_save":
			if($_GET["success"] =="true")
				$res = $modx->parseChunk($tpl, array("message" => "Данные успешно обновлены"));
		break;
		
		//show each album
		case "give_album":
			$sum = "";
			if($_GET["album_id"])
				$id = (int)$_GET["album_id"];
			$photos = $modx->db->makeArray($modx->db->query(
				"SELECT *,
					(SELECT sender
						FROM `modx_a_content`
						WHERE id = t.content_id)
						AS author_id
					FROM modx_a_message t
					WHERE 
						album_id = '".$id."'"
										  ));
		
			if(!empty($photos)){
				foreach($photos as $photo){
					if($photo['author_id'] == $_SESSION['webuser']['internalKey'])
						$delete_button = "";
					else
						$delete_button = "hidden";
					$res .= $modx->parseChunk($tpl, array("user_id" => $photo["author_id"],
															 "album_id" => $photo["album_id"],
															 "photo_id" => $photo["content_id"],
															 "description" => $photo ["discr"],
														  	"delete" => $delete_button
															)
												);
				}
			}else
				$res = $modx->parseChunk($tpl2, array());
		break;	
		
		// show all user's albums
		case "albums":	
			$res = "";
			if($_GET['user_id'] == "")
				$_GET['user_id'] = $user;
			$albums = $modx->db->makeArray($modx->db->query(
				"SELECT * 
					FROM modx_a_album
					WHERE 
					author_id = '".(int)$_GET['user_id']."'"));
			if(!empty($albums))
				foreach($albums as $album){
					$res .= $modx->runSnippet("snippet",array("get" => "all_albums",
																  "tpl" => $tpl,
																  "tpl_no_photo_edit" => $tpl_no_photo_edit,
																  "id" => $album['id'],
																  "title" => $album['name'],
																  "author_id" => (int)$_GET['user_id']
																 ) 
												 );
												
				} 
			else
				$res = $modx->parseChunk($tpl_no_album, array());
		break;
		
		//form cover, name and etc for showing album's covers on the all album's page
		case "all_albums":
			$data = $modx->db->getRow($modx->db->query("SELECT * 
															FROM `modx_a_message` 
															WHERE
																album_id = '".$id."'"
													  )
									 );
			if($data){
				$images = "";
				$path = MODX_BASE_PATH."assets/images/".$author_id."/".$id."/*";
				$folder = glob($path);
				if($author_id != $_SESSION['webuser']['internalKey'])
					$hide_delete = "hidden"; 
				else
					$hide_delete = "";
				$res .= $modx->parseChunk($tpl, array("title" => $title,
													 "id_album" => $id,
													 "photo_name" => $data['photo_name'],
													 "description" => $data['description'],
													  "delete" => $hide_delete,
													  "cover" => str_replace(MODX_BASE_PATH,"/",$folder[0])
													)
										);
			}
			else $res .= $modx->parseChunk($tpl_no_photo_edit, array("title" => $title,
																	 "id_album" => $id
																	)
										  );
		break;
		
		//form information for user's wall form db
		case "wall":
			$userwall = $modx->db->makeArray($modx->db->query(
				"SELECT *,
					(SELECT 
						fullname
						FROM `modx_web_user_attributes`
						WHERE internalKey = t.sender )
						AS 'sender_name',
					(SELECT 
						username
						FROM `modx_web_users`
						WHERE id = t.sender )
						AS 'sender_nick'
					FROM `modx_a_content` t
					WHERE recipient = '".$userid."'
					ORDER BY pub_date DESC"
				));
			foreach($userwall as $message){
				$message_contents = $modx->db->makeArray($modx->db->query(
					"SELECT *
						FROM `modx_a_message`
						WHERE content_id = '".$message['id']."'"
					));
				foreach($message_contents as $message_content){
					switch ($message_content['type_content']){
						//if $message_content['type_content'] = 1 we need show content as text
						case 1:
							$message["message"] = $message_content['text'];
						break;
						//if $message_content['type_content'] = 2 we need show content as youtube video
						case 2:
							$message['youtube'] = $message_content['text'];
						break;
						//if $message_content['type_content'] = 3 we need show content as image
						case 3:
							$message['image_descr'] = $message_content['text'];
							$message['album'] = $message_content['album_id'];
						break;
					}
					
				}
					$res .= $modx->runSnippet("snippet", array("get" => "one_content",
														   "tpl" => $tpl,
														   "tpl_video" => $tpl_video,
														   "tpl_image" => $tpl_image,
														   "tpl_comment" => $tpl_comment,
														   "tpl_delete" => $tpl_delete,
															"tpl_comment_form" => $tpl_comment_form,
														   "message" => $message )
											  );
			}//end wall case
		break;
		
		// form one entry of wall
		case "one_content":
			$message["sender_avatar"] = $modx->runSnippet("snippet",array("get" => "show_avatar",
															  "avatar" => $message['sender']
															 ) 
											 );
			if($message["sender_name"] == "")
				if($message["sender_nick"] == "")
					$message["sender_name"] = "Аноним";
				else 
					$message["sender_name"] = $message["sender_nick"];
			if($message["pub_date"] != "Только что")
					$message["time"] = $modx->runSnippet("snippet",array("get" => "convert_time",
															  "pubtime" => $message["pub_date"]
															 ) 
											 );
			else 
					$message["time"] = $message["pub_date"];
			if($message['youtube'] !="")
					$message['youtube'] = $modx->parseChunk($tpl_video, array("youtube" => $message['youtube']));	
			if($message['album'] !="")
					$message['album'] = $modx->parseChunk($tpl_image, array("image" => $message['id'],
																			"album" => $message['album'],
																			"sender" => $message['sender'],
																			"discription" => $message['image_descr']
																		   ));	
			$message['comment'] = $modx->runSnippet("snippet",array("get" => "give_comment",
															 			"tpl" => $tpl_comment,
																		"tpl_delete" => $tpl_delete,
																		"comment_id" => $message['id']																							) 
											 );
			if($message['sender'] == $_SESSION['webuser']['internalKey'])
					$message['delete_content'] = $modx->parseChunk($tpl_delete, array("comment_id" => $message['id'],
																					  "del_class" => "del-content  del-button",
																					  "title" => "Удалить запись"
																		   ));	
		// show comment's form only authorized users
			if(isset($_SESSION['webuser']['internalKey']))
					$message['content_form'] = $modx->parseChunk($tpl_comment_form,array("id" => $message['id']));
				else
					$message['content_form'] = "";
		//forming 1 block of content
			$res = $modx->parseChunk($tpl, array("sender_name" => $message["sender_name"],
													 "sender_id" => $message['sender'],
													 "sender_avatar" => $message["sender_avatar"],
													 "message" => $message["message"],
													 "time" => $message["time"],
													  'youtube' => $message['youtube'],
													  'image' => $message['album'],
													  'id' => $message['id'],
													  'comments' => $message['comment'],
													  'delete' => $message['delete_content'],
													 'comment_form' => $message['content_form']
													 ));
		break;
		
		// check  - if avatar exist - show - if not - set placeholder
		case "show_avatar":
				if(file_exists(MODX_BASE_PATH."assets/images/avatars/".$avatar))
					$res = $avatar;
				else
					$res = "no_avatar.jpg";
		break;
		
		
		// snippet for sending email
		case "sendmail":
			if (!class_exists('PHPMailer')) {
					  require MODX_BASE_PATH."manager/includes/controls/phpmailer/class.phpmailer.php";
				  }
				  $Password = $modx->config['smtppw'];
				  $Password = substr($Password,0,-7);
				  $Password = str_replace('%','=',$Password);
				  $Password = base64_decode($Password);
				  $mail = new PHPMailer();
				  $mail->IsSMTP();
				  $mail->SMTPDebug  = 0;
				  $mail->SMTPAuth  = true; 
				  $mail->Host      = $modx->config['smtp_host'];
				  $mail->Port      = $modx->config['smtp_port'];
				  $mail->Username  = $modx->config['smtp_username'];
				  $mail->Password  = $Password;
				  $mail->Subject    = $subject;
				$mail->SetFrom($modx->config['smtp_username'], 'Sunflower');
				  $mail->AddAddress($to);
				  $mail->MsgHTML($body);
			$mail->Send();
			break;
		
		//read user data from db and fill on user personal page
		case "userdata":
		//var_dump($modx->documentObject);
			$userdata = $users = $modx->db->getRow($modx->db->query(
				"SELECT *,
					(SELECT 
						username
						FROM `modx_web_users`
						WHERE id = t.internalKey )
						AS 'nick' 
					FROM `modx_web_user_attributes` t
					WHERE internalKey = '".$userid."'"
				));	
			$res .= $modx->parseChunk($tpl, array("fullname" => $userdata['fullname'],
												 "nick" => $userdata['nick'],
												 "email" => $userdata['email'],
												 "city" => $userdata['city'],
												 "birthday" => $userdata['birthday'] 
												 ));
		break;
	}

	return $res;
?>