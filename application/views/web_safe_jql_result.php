<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Make Web Safe JQL Form</title>

	<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
		<div class="starter-template">
            <nav class="navbar navbar-default">
				<div class="container-fluid">
					<div class="navbar-header">
						<h3>Web Safe JQL String</h3>
					</div>
					<a href="<?php echo $base_url ?>" class="btn btn-success" style="float:right;">Home</a>
				</div>
			</nav>
			<form method="post" id="jqlform" name="jqlform">
				<div class="row control-group">
					<div class="form-group col-xs-12 floating-label-form-group controls">
						<textarea rows="5" class="form-control" id="jql" name="jql">https://tempurer.atlassian.net/rest/api/2/search/?jql=<?php echo $jql;?></textarea>
					</div>
				</div>
			</form>
		</div>
    </div>

</body>
</html>