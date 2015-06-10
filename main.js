$(document).ready(function() {
	var _get = {};
    location.search.substr(1).replace(/([^=&]+)=([^&]*)/g, function(o, k, v) {
       _get[decodeURIComponent(k)] = v;
    });

    $("[name='photo[id]']").val(_get.album_id);
    $("#user_fullname").html(_get.user);
    /*validation*/

	$(".validate-form").each(function(){
        $(this).validate({
        focusInvalid: true,
        errorPlacement: function(error,element) {

        	error.appendTo( element.parent("td").next("td") );
			error.insertAfter(element);
            error.parent('p').addClass('error');
        },
        success: function(valid) {
		  valid.parent('p').removeClass('error');
		},
		rules:{ 
        	name:{
                required: true
            },
            phone:{
                required: true
            },
            message:{
                required: true
            },
            email:{
                required: true,
                email: true
            },
            captcha:{
                required: true
            },
            password:{
                required: true
            },
            npass: {
                required: true
            }
            
       },
       messages:{
        	name:{
                required: "Поле Имя не заполнено"
            },
            phone:{
                required: "Поле Телефон не заполнено"
            },
            message:{
                required: "Поле Сообщение не заполнено"
            },
        	email:{
        		required: "Поле E-mail не заполнено",
                email: "Вы ввели неправильный формат email"
            },
            captcha:{
                required: "Поле c кодом не заполнено"
            },
            password:{
                required: "Поле Пароль не заполнено"
            },
            npass: {
                required: "Повторите пароль"
            }
        }
    });
	});
    
    $(".validate-form-order input.error").each(function(){
        $(this).parent('div').addClass('error');
    });

    $("a.js_close").click(function(){				
		$('.popup').fadeOut(100);
		$('#overlay').remove('#overlay');
		return false;
	});
  
    
    //check authentification

    $(".auth").on("click", function(){
        var _this = $("[name='auth_pass']");
        var a = $("[name='auth_email']").val();
        var b = _this.val();
        var c = _this.attr('data-check');
        var captcha = $("[name='g-recaptcha-response']").val();
        if(a != "" && b != ""){
            if(c == "1" && captcha == ""){
                $(".check_auth").removeClass("hidden");
                $(".check_auth").addClass("show");
                $(".check_auth").text("Вы не прошли проверку captcha");
            }else{
                $.ajax({
                        type: "POST",
                        data: "ajax=auth&email="+a+"&pass="+b+"&captcha="+captcha+"&second_login="+c,
                        success: function(result){
                        	var key = "";
                            var data = $.parseJSON(result);
                            switch (data.key){
                                case "0":
                                    key = "Вы не заполнили форму";
                                break;
                                case "11":
                                    key = "Пользователя с такими данными не существует!";
                                break;
                                case "12":
                                    key = "Ваш аккаунт заблокирован.";
                                break;
                                case "13":
                                    key = "Вы ввели не верный пароль!";
                                    $(".block-captcha").removeClass("hidden");
                                    $(".block-captcha").addClass("show");
                                    _this.attr('data-check',1); 
                                break;
                                case "14":
                                    key = "Captcha не прошла проверку";
                                break;
                                case "20":
                                    location.href = "/"+data.username;
                                break;
                            }
                            if(key!=""){
    	                        $(".check_auth").removeClass("hidden");
    	                        $(".check_auth").addClass("show");
    	                        $(".check_auth").text(key);
    						}                      
                        },        
                        error:function(){ 
                            alert("Возникли проблемы с отправкой формы");
                        }  
                })
            }
        }else{
        	$(".check_auth").removeClass("hidden");
	        $(".check_auth").addClass("show");
            $(".check_auth").text("Вы заполнили не все поля");
        }
        return false;
    })

    
    // send user registration datum to plugin and recieve answer from him
    $(".reg").on("click", function(){
        var a = $("[name='reg_email']").val();
        var b = $("[name='reg_pass']").val();
        if(a!= "" && b!= ""){
           $.ajax({
                    type: "POST",
                    data: "ajax=reg&reg[email]="+a+"&reg[pass]="+b,
                    success: function(result){
                    	var key = "";
                        switch (result){    
                            case "13":
                                key = "Пользователь с таким email уже зарегистрирован"
                            break;
                            case "0":
                                key = "Была получена пустая форма";
                            break;
                            case "20":
                                location.href = "/editdata";
                            break;
                        } 
                        if(key!=""){
	                        $(".check_reg").removeClass("hidden");
	                        $(".check_reg").addClass("show");
	                        $(".check_reg").text(key);
						}      
                    },        
                    error:function(){ 
                        alert("Возникли проблемы с отправкой формы");
                    }

            })
        }else{
            $(".check_reg").removeClass("hidden");
	        $(".check_reg").addClass("show");
            $(".check_reg").text("Вы заполнили не все поля");
        }
        return false;
    })
    

    // receive user email for recover password and send him to plugin
    $(".forg").on("click", function(){
        var a = $("[name='forg_email']").val();
        var captcha = $("[name='g-recaptcha-response']").val();
        if(a != ""){
            if(captcha != ""){
               $.ajax({
                        type: "POST",
                        data: "ajax=forg&forg[email]="+a+"&captcha="+captcha,
                        success: function(result){
                            var key = "";
                            switch (result){    
                                case "11":
                                    key = "Пользователя с таким email не существует!";
                                break;
                                case "0":
                                    key = "Получена пустая форма";
                                break;
                                case "20":
                                  //  key = "Новый пароль Вам отослан в виде письма электронной почты";
                                    location.href = "/";
                                break;
                            } 
                            if(key!=""){
                                $(".check_forg").removeClass("hidden");
                                $(".check_forg").addClass("show");
                                $(".check_forg").text(key);
                            }      
                        },        
                        error:function(){ 
                            alert("Возникли проблемы с отправкой формы");
                        }
                })
            }else{
                $(".check_forg").removeClass("hidden");
                $(".check_forg").addClass("show");
                $(".check_forg").text("Captcha не прошла проверку");
            }
        }else{
            $(".check_forg").removeClass("hidden");
            $(".check_forg").addClass("show");
            $(".check_forg").text("Вы не вписали email");
        }
        return false;
    })


	//user logout
	$(".logout").on("click", function(){
		$.ajax({
                type: "POST",
                data: "ajax=logout",
                url: "/",
                success: function(result){   
                        location.href = "/";     
                    },        
                    error:function(){ 
                        location.href = "/";
                    }
            })
		return false;
	})

    // catch changing of user data from form and send to DB by ajax
    $(".change").on("click", function(){
        var email = $("[name='user[email]']").val();
        var oldpass = $("[name='user[old_password]']").val();
        var newpass = $("[name='user[new_password]']").val();
        var name = $("[name='user[name]']").val();
        var nick = $("[name='user[nick]']").val();
        var city = $("[name='user[city]']").val();
        var birthday = $("[name='user[birthday]']").val();
        $.ajax({
                type: "POST",
                data: "ajax=change&user[email]="+email+"&user[old_password]="+oldpass+"&user[new_password]="+newpass+"&user[name]="+name+"&user[nick]="+nick+"&user[city]="+city+"&user[birthday]="+birthday,
                url: "/",
                success: function(result){  
                    var key = "";
                    var data = $.parseJSON(result);
                    var k = data.key.toString();
                        switch (k){    
                            case "11":
                                key = "Вы ввели неправильный старый пароль!";
                            break;
                            case "13":
                                key = "Пользователь с таким ником уже зарегистрирован!";
                            break;
                            case "20":
                                 location.href = "/"+data.username+"?success=true";
                            break;
                            default:
                                alert("fes");
                            break;
                        } 
                        if(key!=""){
                            $(".check_change").removeClass("hidden");
                            $(".check_change").addClass("show");
                            $(".check_change").text(key);
                        }          
                    },        
                    error:function(){ 
                        alert("Возникли проблемы с отправкой формы");
                    }
            })
        return false;
    })
    
    // nice calendar on the editpage
    $(function () {
        $('#datetimepicker9').datetimepicker({
                viewMode: 'years',format: 'YYYY-MM-DD'
        });
    })

    //show button "Отправить" on the user page
    $(".message_text").on("click", function(){
        $(".show_button").removeClass("hidden");
        $(".show_button").addClass("show");
        return false;
    })

    //show button "добавить видео" on the user page
    $("#youtube").on("click", function(){
        $(".show_youtube").removeClass("hidden");
        $(".show_youtube").addClass("show");
        return false;
    })

    //show comments for current content block on the user page
    $(".button_block").live("click", function(){
        var _this = $(this);
        var a = ".show_comment_wall_"+_this.attr('data-content-id');
        $(a).toggleClass("hidden");
    })

    //send entry from form on user's personal page
    $("#send_message").on("click", function(){
        var _this = $(this);
        var message = $("[name='message']").val();
        var youtube = $("[name='youtube']").val();
        var sender = _this.attr('data-id');
        var recipient = _this.attr('data-userid');
        if(message != "" || youtube != ""){
               $.ajax({
                        type: "POST",
                        url: "/",
                        data: "ajax=message&message="+message+"&sender="+sender+"&recipient="+recipient+"&youtube="+youtube,
                        success: function(result){
                            console.log(result);
                            $(".first_content").after(result);  
                            $("[name='message']").val("");
                            $("[name='youtube']").val(""); 
                            $(".show_button").removeClass("show");
                            $(".show_button").addClass("hidden");
                        },        
                        error:function(){ 
                            alert("Возникли проблемы с отправкой формы");
                        }
                })
            }else{
                $(".check_message").removeClass("hidden");
                $(".check_message").addClass("show");
                $(".check_message").text("Сообщение пустое");
            }
        return false;
    })

    // send name of new user's album to plugin
    $(".new_album").on("click", function(){
        var _this = $(this);
        var user = _this.attr('data-userid'); 
        var a = $("[name='album_name']").val();
        if(a!= ""){
           $.ajax({
                    type: "POST",
                    data: "ajax=newalbum&albumname="+a+"&user="+user,
                    success: function(result){
                            location.href = "/album?album_id="+result; 
                    },        
                    error:function(){ 
                        alert("Возникли проблемы с отправкой формы");
                    }

            })
        }else{
            $(".check_album").removeClass("hidden");
            $(".check_album").addClass("show");
            $(".check_album").text("Вы не указали название альбома");
        }
        return false;
    })

    // button "add one more foto" on new-report page
    $(".add-foto").live("click", function(){
        $("<input type='file' name='photo[foto][]'/><div><label>Description<textarea name='photo[description][]' class='form-control'></textarea></label></div>").insertBefore("#sent");
        return false;
    });

    //Удаление альбома
    $(".del-report").on("click", function(){
           var _this = $(this);
            if (confirm("Вы уверены, что хотите удалить весь фотоальбом?")) {
                $.ajax({
                    type: "POST",
                    data: "ajax=del_album&album="+_this.attr('data-folder'),
                    success: function(result){
                            location.href = "/photos";
                        },
                        error:function(){
                    }
                })

            }   
         return false;
    });

    // удаление фотографии из фотоотчета

    $(".del-foto").live("click", function(){
           var _this = $(this);
        if (confirm("Вы уверены, что хотите удалить фотографию?")) {
           $.ajax({
                type: "POST",
                data: "ajax=del_foto&foto="+_this.attr('data-img'),
                success: function(result){
                      $(".photo_"+_this.attr('data-img')).addClass("hidden");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для удаления фотографии");
                }
            })
        }
        return false;
    });

    //do this photo avatar
    $(".on_avatar").live("click", function(){
        var _this = $(this);
        $.ajax({
                type: "POST",
                data: "ajax=on_avatar&foto="+_this.attr('data-img'),
                success: function(result){
                    $(".message_"+_this.attr('data-img')).removeClass("hidden");
                    $(".message_"+_this.attr('data-img')).addClass("show");
                    $(".message_"+_this.attr('data-img')).text("Ваша новая аватар");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для удаления фотографии");
                }
        })
        return false;
    });

     //save comment to DB
    $(".comment").live("click", function(){
        var _this = $(this);
        var content_id = _this.attr('data-content-id');
        var a = $("[name='comment_"+content_id+"']").val();
        if(a!= ""){
            $.ajax({
                    type: "POST",
                    url:"/",
                    data: "ajax=comment&contentid="+content_id+"&comment_text="+a,
                    success: function(result){
                        $(".first_comment_"+content_id).after(result);  
                        $("[name='comment_"+content_id+"']").val("");
                     },
                    error:function(){
                        alert("Возникли проблемы с отправкой данных для удаления фотографии");
                    }
            })
        }else{
            $(".check_comment_"+content_id).removeClass("hidden");
            $(".check_comment_"+content_id).addClass("show");
            $(".check_comment_"+content_id).text("Вы ничего не написали");
        }
        return false;
    });

    // removal of entries from the wall
    $(".del-content").live("click", function(){
           var _this = $(this);
        if (confirm("Вы уверены, что хотите удалить запись?")) {
           $.ajax({
                type: "POST",
                url:"/",
                data: "ajax=del_content&content="+_this.attr('data-сontent-id'),
                success: function(result){
                    $(".comment_"+_this.attr('data-сontent-id')).addClass("hidden"); 
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для удаления записи");
                }
            })
        }
    });

    //remove comment from current content
    $(".del-comment").live("click", function(){
        var _this = $(this);
        if (confirm("Вы уверены, что хотите удалить комментарий?")) {
           $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=del_comment&content="+_this.attr('data-сontent-id'),
                success: function(result){
                    $(".comment_"+_this.attr('data-сontent-id')).addClass("hidden");  
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для удаления комментария");
                }
            })
        }
    });

    //add people to friends
    $(".glyphicon-plus").live("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=add_friend&user_id="+id,
                success: function(result){
                    $(".neg_" + id).removeClass("glyphicon-");
                    $(".neg_" + id).addClass("glyphicon-remove-circle");
                    $(".neg_" + id).attr('title', 'Отозвать запрос в друзья');
                    _this.removeClass("glyphicon-plus");
                    _this.addClass("glyphicon-envelope");
                    _this.attr('title', 'Отправлен запрос в друзья');
                    $(".message_" + id).removeClass("hidden");
                    $(".message_" + id).addClass("show");
                    $(".message_" + id).text("Отправлен запрос на добавление в друзья");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друзья");
                }
            })
    });

    //confirm person as friend
    $(".glyphicon-check").on("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=confirm_friend&user_id="+id,
                success: function(result){
                    $(".neg_" + id).removeClass("glyphicon-minus");
                    $(".neg_" + id).addClass("glyphicon-remove");
                    $(".neg_" + id).attr('title', 'Убрать из друзей');
                    _this.removeClass("glyphicon-check");
                    _this.addClass("glyphicon-ok");
                    _this.attr('title', 'Вы друзья');
                    $(".message_" + id).removeClass("hidden");
                    $(".message_" + id).addClass("show");
                    $(".message_" + id).text("Теперь Вы друзья");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друзья");
                }
            })
    });

    //leave person in followers
    $(".glyphicon-minus").on("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=followers&user_id="+id,
                success: function(result){
                    $(".pos_" + id).removeClass("glyphicon-check");
                    $(".pos_" + id).addClass("glyphicon-eye-open");
                    $(".pos_" + id).attr('title', 'Ваш поклонник. Добавить в друзья');
                    _this.removeClass("glyphicon-minus");
                    _this.addClass("glyphicon-remove");
                    _this.attr('title', 'Убрать из поклонников');
                    $(".message_" + id).removeClass("hidden");
                    $(".message_" + id).addClass("show");
                    $(".message_" + id).text("Теперь это Ваш поклонник");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друзья");
                }
            })
    });

    //cancel friendship
    $(".glyphicon-remove").live("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=cancel_friendship&user_id="+id,
                success: function(result){
                    $(".pos_" + id).removeClass("glyphicon-ok");
                    $(".pos_" + id).removeClass("glyphicon-eye-open");
                    $(".pos_" + id).addClass("glyphicon-plus");
                    $(".pos_" + id).attr('title', 'Добавить в друзья');
                    _this.removeClass("glyphicon-remove");
                    $(".message_" + id).removeClass("hidden");
                    $(".message_" + id).addClass("show");
                    $(".message_" + id).text("Вы разорвали отношения с пользователем");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друзья");
                }
            })
    });

    //cancel request to friendship
    $(".glyphicon-remove-circle").on("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=cancel_request_friendship&user_id="+id,
                success: function(result){
                     $(".pos_" + id).removeClass("glyphicon-envelope");
                    $(".pos_" + id).addClass("glyphicon-plus");
                    $(".pos_" + id).attr('title', 'Добавить в друзья');
                    _this.removeClass("glyphicon-remove-circle");
                    $(".message_" + id).removeClass("hidden");
                    $(".message_" + id).addClass("show");
                    $(".message_" + id).text("Запрос в друзья отозван");
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друзья");
                }
            })
    });

    //cancel request to friendship
    $(".friend_to_photo").on("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        $.ajax({
                type: "POST",
                url: "/",
                data: "ajax=add_friend_to_photo&user_id="+id+"&photo_id="+$(".comment").attr("data-content-id"),
                success: function(result){
                    $(".friends_on_photo").html(result);
                 },
                error:function(){
                    alert("Возникли проблемы с отправкой данных для добавления в друга к фотографии");
                }
            })
    });

    //Delete user from photo
    $(".del_from_photo").live("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        if(confirm("Вы уверены, что Вас там не было?")){
            $.ajax({
                    type: "POST",
                    url: "/",
                    data: "ajax=del_user_from_photo&user_id="+id+"&photo_id="+$(".comment").attr("data-content-id"),
                    success: function(result){
                        $(".user_"+id).addClass("hidden");  
                     },
                    error:function(){
                        alert("Возникли проблемы с отправкой данных для удаления Вас с фотографии");
                    }
            })
        }
    });

    //show dialog between users on message page
    $(".give_message").on("click", function(){
        $("#chat_place").stopTime("mytimer"); 
        var _this = $(this);
        var id = _this.attr('data-user-id');
        chat(id);
        $.ajax({
                    type: "POST",
                    url: "/",
                    data: "ajax=show_message_form&user_id="+id,
                    success: function(result){
                        $(".chat_form_place").html(result);  
                        $("#chat_friends").children().removeClass("active");
                        $(".chat_form").removeClass("hidden");
                        $(".person_"+id).addClass("active");
                     },
                    error:function(){
                        alert("Возникли проблемы с отправкой данных для вывода сообщений");
                    }
            })
        $("#chat_place").everyTime(1000, "mytimer",function() {
            chat(id);
        });
        return false;
    });

    $(".chat_send").live("click", function(){
        var _this = $(this);
        var id = _this.attr('data-user-id');
        var text = $("[name='chat_form']").val();
        if(text != ""){
            $.ajax({
                    type: "POST",
                    url: "/",
                    data: "ajax=send_message&user_id="+id+"&text="+text,
                    success: function(result){
                        $(".last_message").removeClass("hidden");
                        $(".last_message").html(result);  
                        $("[name='chat_form']").val("");
                     },
                    error:function(){
                        alert("Возникли проблемы с отправкой данных для вывода сообщений");
                    }
            })
        }
    })
    return false;

});

function chat(id){
    $.ajax({
                    type: "POST",
                    url: "/",
                    data: "ajax=show_messages&user_id="+id,
                    success: function(result){
                        $("#chat_place").html(result);  
                     },
                    error:function(){
                        alert("Возникли проблемы с отправкой данных для вывода сообщений");
                    }
            })
    return false;
}