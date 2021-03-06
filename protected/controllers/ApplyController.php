<?php

class ApplyController extends Controller
{
	public function actionCreate($j_id, $c_id)
	{
        if(Yii::app()->session['role'] == 1)
        {
            //following code snippet is to calculate the percentage placed for the <dept,programme> tuple of the student
            $stud = Student::model()->findByPk(Yii::app()->user->id);
            $st = new Student();
            $st->st_id = Yii::app()->user->id;

            $dept = $stud->getAttribute("dept");
            $programme = $stud->getAttribute("programme");

            $sql =  Yii::app()->db->createCommand("select count(*) as cnt from student where dept=\"".$dept."\" and programme = \"".$programme."\"")->queryRow();
            $totalReg = $sql['cnt'];

            $sql =  Yii::app()->db->createCommand("select count(*) as cnt from (select distinct(oTemp.st_id) from offers as oTemp) as o, student as s where o.st_id = s.st_id and s.dept=\"".$dept."\" and s.programme = \"".$programme."\"")->queryRow();
            $actualStudPlaced = $sql['cnt'];

            if($totalReg==0) $percent=0;
            else $percent = ($actualStudPlaced*100)/$totalReg;

            if($percent < 80)
            {

                $ppoCheck=  Yii::app()->db->createCommand("select c_id from offers where st_id = ".Yii::app()->user->id." and ppo = 'Y' and accepted = 'Y'")->queryAll();
                
                $offerCheck=  Yii::app()->db->createCommand("select c_id from offers where st_id = ".Yii::app()->user->id." and ppo <> 'Y' and accepted = 'Y'")->queryAll();
                if(count($offerCheck)!=0) //checking whether he has already received a non-PPO offer or not
                {
                    Yii::app()->user->setFlash('error','Slow down! You are already placed. Let others have a chance my friend');
                    $this->redirect(array('student/viewJobs'));
                }
                else if(count($ppoCheck)!=0)    //if he already has an accepted PPO
                {
                    
                    $dreamCheck = Yii::app()->db->createCommand("select st_id from apply where st_id = ".Yii::app()->user->id)->queryAll();
                    if(count($dreamCheck)!=0) //checking if he has already applied to his dream company
                    {
                        Yii::app()->user->setFlash('error','You already have an accepted PPO offer and have already applied to your dream company');
                        $this->redirect(array('student/viewJobs'));
                        
                    }
                }
            }

            $sqlcount =  Yii::app()->db->createCommand("select count(*) from job_profile where j_id = ".$j_id." and c_id = ".$c_id)->queryScalar();
            $sql = "select * from job_profile where j_id = ".$j_id." and c_id = ".$c_id;

            $dataProvider = new CSqlDataProvider($sql, array(
                'db' => Yii::app()->db,
                'keyField' => 'j_id',
                'totalItemCount' => $sqlcount
            ));

            $sqlcount =  Yii::app()->db->createCommand("select count(*) from cv_table where st_id = ".Yii::App()->user->id)->queryScalar();
            $sql = "select * from cv_table where st_id = ".Yii::App()->user->id;
            $res =  Yii::app()->db->createCommand("select * from cv_table where st_id = ".Yii::App()->user->id)->queryAll();



            $cv_list = array();

            foreach($res as $item){
                $cv_list[$item["cv_id"]] = $item["cv_name"];
            }

            $this->render('create',array(
                'dataProvider'=>$dataProvider,
                'cv_list'=>$cv_list,
                'j_id'=>$j_id,
                'c_id'=>$c_id,
            ));

        }

	}

    public function actionSave($j_id,$c_id)
    {
        if(Yii::app()->session['role'] == 1)
        {
            if(!(isset($_POST['cv_id']) && isset($j_id) && isset ($c_id))){
                Yii::app()->user->setFlash('error','Error');
                $this->redirect(array('index'));
            }
            $studinfo = Student::model()->findByPk(Yii::App()->user->id);

            $dept = $studinfo->getAttribute("dept");
            $cpi = $studinfo->getAttribute("cpi");

            $sqlcount =  Yii::app()->db->createCommand("select count(*) from job_profile_branches as jpb where j_id =
         ".$j_id." and c_id = ".$c_id." and dept = '".$dept."'" )->queryScalar();

            $jobinfo = JobProfile::model()->find('j_id = ? and c_id = ? and CURRENT_TIMESTAMP<deadline',array($j_id,$c_id));

            if(count($jobinfo))
            {
                $cutoff = $jobinfo->getAttribute("cpi_cutoff");

                if($sqlcount && $cpi>=$cutoff)
                {
                    try{
                        $st_id = Yii::App()->user->id;
                        $connection = Yii::App()->db;
                        $sql = "INSERT INTO apply (j_id,c_id,cv_id,st_id) values(:j_id,:c_id,:cv_id,:st_id)";
                        $command = $connection->createCommand($sql);
                        $command->bindParam(":j_id",$j_id,PDO::PARAM_STR);
                        $command->bindParam(":c_id",$c_id,PDO::PARAM_STR);
                        $command->bindParam(":cv_id",$_POST['cv_id'],PDO::PARAM_STR);
                        $command->bindParam(":st_id",$st_id,PDO::PARAM_STR);
                        $command->execute();
                        Yii::app()->user->setFlash('success','Successfully Applied ');
                    }catch(Exception $e){
                        Yii::app()->user->setFlash('error','You are trying to reapply');
                    }
                }else{
                    Yii::app()->user->setFlash('error','CPI cutoff not satisfied ');


                }

            }else{
                Yii::app()->user->setFlash('error','Deadline has been expired');


            }

            $this->redirect(array('site/index'));
        }
    }

	public function actionDelete()
	{
		$this->render('delete');
	}

	public function actionIndex()
	{
		$this->render('index');
	}

	public function actionUpdate()
	{
		$this->render('update');
	}

    public function actionViewCompDetails ($c_id)
    {
        $model=Company::model()->findByPk($c_id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        $this->render('viewCompDetails',array('model'=>$model));

    }

	public function actionView()
	{
		if(Yii::app()->session['role'] == 1)
        {
            $sqlcount =  Yii::app()->db->createCommand("select count(*) from student as s, apply as a, job_profile as jp
			where s.st_id=a.st_id and a.j_id = jp.j_id and a.c_id = jp.c_id and s.st_id = ".Yii::App()->user->id)->queryScalar();
            $sql = "select a.cv_id, a.j_id, a.c_id, a.tstamp, c.name as cname, j.description, j.ctc, j.cpi_cutoff, j.approved, j.deadline, s.st_id, s.roll_no, s.name from student as s, apply as a, job_profile as j, company as c
			where s.st_id=a.st_id and a.j_id = j.j_id and a.c_id = j.c_id and j.c_id = c.c_id and s.st_id = ".Yii::App()->user->id;
            $dataProvider = new CSqlDataProvider($sql, array(
                'db' => Yii::app()->db,
                'keyField' => 'j_id',
                'totalItemCount' => $sqlcount
            ));

            $this->render('view',array(
                'dataProvider'=>$dataProvider,
            ));
        }
        else if(Yii::app()->session['role'] == 2)
        {
            $sqlcount =  Yii::app()->db->createCommand("select count(*) from student as s, apply as a, job_profile as jp
			where s.st_id=a.st_id and a.j_id = jp.j_id and a.c_id = jp.c_id and jp.c_id = ".Yii::App()->user->id)->queryScalar();
            $sql = "select a.cv_id, a.j_id, a.c_id, a.tstamp, c.name as cname, j.description, j.ctc, j.cpi_cutoff, j.approved, j.deadline, s.st_id, s.roll_no, s.name from student as s, apply as a, job_profile as j, company as c
			where s.st_id=a.st_id and a.j_id = j.j_id and a.c_id = j.c_id and and j.c_id = c.c_id and j.c_id = ".Yii::App()->user->id;

            $dataProvider = new CSqlDataProvider($sql, array(
                'db' => Yii::app()->db,
                'keyField' => 'j_id',
                'totalItemCount' => $sqlcount
            ));

            $this->render('view',array(
                'dataProvider'=>$dataProvider,
            ));


        }
        else
            $this->redirect(array('index.php?r=site/index'));
	}

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
            //'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions'=>array('create','index','update','delete','admin','view','save','viewCompDetails'),
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }

	// Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/
}