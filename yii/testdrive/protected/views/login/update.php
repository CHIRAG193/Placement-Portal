<?php
/* @var $this LoginController */
/* @var $model Login */

$this->breadcrumbs=array(
	'Logins'=>array('index'),
	$model->email_id=>array('view','id'=>$model->email_id),
	'Update',
);

$this->menu=array(
	array('label'=>'List Login', 'url'=>array('index')),
	array('label'=>'Create Login', 'url'=>array('create')),
	array('label'=>'View Login', 'url'=>array('view', 'id'=>$model->email_id)),
	array('label'=>'Manage Login', 'url'=>array('admin')),
);
?>

<h1>Update Login <?php echo $model->email_id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>