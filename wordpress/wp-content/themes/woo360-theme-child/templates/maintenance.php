<?php
 /**
 * Maintenance mode template
 */
 ?>
 <!DOCTYPE html>
 <html>
 <head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="profile" href="http://gmpg.org/xfn/11">
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
 <title>Site Suspended</title>
 </head>
 <body class="text-center">
 
 <style>

html, body {
  height: 100%;
  background-color: #333;
}

body {
  display: -ms-flexbox;
  display: flex;
  box-shadow: inset 0 0 5rem rgba(0, 0, 0, .5);
}

.cover-container {
  max-width: 42em;
}

.cover {
    margin-top: 30vh;
    padding: 0 1.5rem;
}
 </style>
 <div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">


      <main role="main" class="inner cover">
      
      </main>

      <div class="card">
      <div class="card-header">
      <h1 class="cover-heading">This site is currently unavailable</h1>
      </div>
  <div class="card-body">
  <h5 class="card-title">Please try again later.</h5>
    <p class="card-text">This site has been suspended.</p>
    <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync"></i> Reload</button>
    
    
  </div>
  <div class="card-footer text-muted">
        If you are the owner of this site, contact your administrator.
        </div>
</div>

    </div>

 </body>
 </html>