<?php
/* @var $this SiteController */

$this->pageTitle = Yii::app()->name;
?>

<!-- Slider -->
<div class = "main_slider" style = "left: -72px; height: 412px; top: -160px; width: 1240px; background: url(<?php print Yii::app()->request->baseUrl;?>/images/BOOTSTRAP-001.jpg);">
	<a id = "buy_destination" href = "<?php print Yii::app()->request->baseUrl;?>/shop/">
	<button style = "cursor: pointer; position: absolute; left: 64px; top: 280px; border: double;" type = "button" style = "cursor: pointer; font-size: 40px;" class = "btn btn-default btn-xs">Buy Stuff</button>
	</a>
	<a href = "<?php print Yii::app()->request->baseUrl;?>/donate/">
	<button style = "cursor: pointer; position: absolute; top: 200px; right: 270px; top: 280px; border: double;" type = "button" style = "cursor: pointer; font-size: 40px;" class = "btn btn-default btn-xs">Donate Stuff</button>
	</a>
</div>
