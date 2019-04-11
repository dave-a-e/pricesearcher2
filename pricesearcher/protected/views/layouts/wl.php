<!DOCTYPE html>
<html lang="en">
<head>
<title><?php print Yii::app()->name;?></title>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="Colo Shop Template">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/styles/bootstrap4/bootstrap.min.css">
<link href="<?php echo Yii::app()->request->baseUrl; ?>/plugins/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/plugins/OwlCarousel2-2.2.1/owl.carousel.css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/plugins/OwlCarousel2-2.2.1/owl.theme.default.css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/plugins/OwlCarousel2-2.2.1/animate.css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/styles/main_styles.css">
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->request->baseUrl; ?>/styles/responsive.css">

<script src="<?php echo Yii::app()->request->baseUrl; ?>/js/jquery-3.2.1.min.js"></script>
</head>

<body>

<div class="super_container">

	<!-- Header -->

	<header class="header trans_300">

		<!-- Top Navigation -->

		<div class="top_nav">
			<div class="container">
				<div class="row">
					<div class="col-md-6">
						<div class="top_nav_left"></div>
					</div>
					<div class="col-md-6 text-right">
						<div class="top_nav_right">
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Main Navigation -->

		<div class="main_nav_container">
			<div class="container">
				<div class="row">
					<div class = "col-lg-12">
						<div class = "logo_container">
							<a href = "<?php echo Yii::app()->request->baseUrl; ?>/">
							<img style = "inline-block" src = "<?php print Yii::app()->request->baseUrl; ?>/images/Give-4-2-Chance-Logo-000.png">&nbsp;Pricesearcher</a>
						</div>
						<nav class="navbar">
							<div class="hamburger_container">
								<i class="fa fa-bars" aria-hidden="true"></i>
							</div>
						</nav>
					</div>
				</div>
			</div>
		</div>

	</header>

	<div class="fs_menu_overlay"></div>
	<div class="hamburger_menu">
		<div class="hamburger_close"><i class="fa fa-times" aria-hidden="true"></i></div>
		<div class="hamburger_menu_content text-right">
			<ul class="menu_top_nav">
			</ul>
		</div>
	</div>

	<br />
	&nbsp;
	<br />
	&nbsp;
	<br />
	&nbsp;
	<br />
	&nbsp;
	<br />
	&nbsp;
	<br />
	&nbsp;
	<br />

	<div class = "container">

	<?php echo $content; ?>

	</div>

	<!-- Footer -->

	<footer class = "footer">
		<div class="container">
			<div class="row">
				<div class="col-lg-6">
					<div class="footer_nav_container d-flex flex-sm-row flex-column align-items-center justify-content-lg-start justify-content-center text-center">
						<ul class="footer_nav">
						</ul>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="footer_social d-flex flex-row align-items-center justify-content-lg-end justify-content-center">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="footer_nav_container">
						<div class="cr">&copy; 2019 <?php print Yii::app()->name;?>. All Rights Reserved. A Scrupulous IT Website, based on the ColoShop template by <a href="https://colorlib.com/wp/template/coloshop/" target = "_new">Colorlib</a></div>
					</div>
				</div>
			</div>
		</div>
	</footer>

</div>

<script src="<?php echo Yii::app()->request->baseUrl; ?>/styles/bootstrap4/popper.js"></script>
<script src="<?php echo Yii::app()->request->baseUrl; ?>/styles/bootstrap4/bootstrap.min.js"></script>
<script src="<?php echo Yii::app()->request->baseUrl; ?>/plugins/Isotope/isotope.pkgd.min.js"></script>
<script src="<?php echo Yii::app()->request->baseUrl; ?>/plugins/OwlCarousel2-2.2.1/owl.carousel.js"></script>
<script src="<?php echo Yii::app()->request->baseUrl; ?>/plugins/easing/easing.js"></script>
<script src="<?php echo Yii::app()->request->baseUrl; ?>/js/custom.js"></script>

</body>

</html>
