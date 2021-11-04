<!DOCTYPE html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- jQuery first, then Tether, then Bootstrap JS. -->
    <link href="/css/signin.css" rel="stylesheet">
    <script src="/js/jquery.min.js"></script>
    <script src="/js/tether.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/ie10-viewport-bug-workaround.js"></script>
    <script>
      function login(form) {
        $.getJSON('/',{json: 'login', login: $(form).find('#inputLogin').val(), passwd: $(form).find('#inputPassword').val()}).done(function(data) {
          location.reload();
        });
        return false;
      }
    </script>
  </head>
  <body>

    <div class="container">

      <?php
        if(isset($_SESSION['login'])) {
          echo "<div class='alert alert-danger'><strong>Внимание</strong> Указанные логин или пароль не верны</div>";
        }
      ?>
      <form class="form-signin" onSubmit='return login(this);'>
        <center><h2 class="form-signin-heading">Аутентификация</h2></center>
        <label for="inputLogin" class="sr-only">Логин</label>
        <input type="text" id="inputLogin" class="form-control" placeholder="Пользователь Asterisk" required autofocus>
        <label for="inputPassword" class="sr-only">Пароль</label>
        <input type="password" id="inputPassword" class="form-control" placeholder="Пароль" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Войти</button>
      </form>

    </div> <!-- /container -->

  </body>
</html>
