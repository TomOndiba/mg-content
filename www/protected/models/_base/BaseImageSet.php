<?php

/**
 * This is the model base class for the table "image_set".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "ImageSet".
 *
 * Columns in table "image_set" available as properties of the model,
 * followed by relations of table "image_set" available as properties of the model.
 *
 * @property integer $id
 * @property string $name
 * @property integer $locked
 * @property string $more_information
 * @property integer $licence_id
 * @property string $created
 * @property string $modified
 *
 * @property Game[] $games
 * @property Licence $licence
 * @property Image[] $images
 * @property SubjectMatter[] $subjectMatters
 */
abstract class BaseImageSet extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'image_set';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'ImageSet|ImageSets', $n);
	}

	public static function representingColumn() {
		return 'name';
	}

	public function rules() {
		return array(
			array('name, created, modified', 'required'),
			array('locked, licence_id', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>64),
			array('more_information', 'safe'),
			array('locked, more_information, licence_id', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, locked, more_information, licence_id, created, modified', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'games' => array(self::MANY_MANY, 'Game', 'game_to_image_set(image_set_id, game_id)'),
			'licence' => array(self::BELONGS_TO, 'Licence', 'licence_id'),
			'images' => array(self::MANY_MANY, 'Image', 'image_set_to_image(image_set_id, image_id)'),
			'subjectMatters' => array(self::MANY_MANY, 'SubjectMatter', 'image_set_to_subject_matter(image_set_id, subject_matter_id)'),
		);
	}

	public function pivotModels() {
		return array(
			'games' => 'GameToImageSet',
			'images' => 'ImageSetToImage',
			'subjectMatters' => 'ImageSetToSubjectMatter',
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'name' => Yii::t('app', 'Name'),
			'locked' => Yii::t('app', 'Locked'),
			'more_information' => Yii::t('app', 'More Information'),
			'licence_id' => null,
			'created' => Yii::t('app', 'Created'),
			'modified' => Yii::t('app', 'Modified'),
			'games' => null,
			'licence' => null,
			'images' => null,
			'subjectMatters' => null,
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('locked', $this->locked);
		$criteria->compare('more_information', $this->more_information, true);
		$criteria->compare('licence_id', $this->licence_id);
		$criteria->compare('created', $this->created, true);
		$criteria->compare('modified', $this->modified, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'pagination'=>array(
        'pageSize'=>Yii::app()->params['pagination.pageSize'],
      ),
		));
	}
}