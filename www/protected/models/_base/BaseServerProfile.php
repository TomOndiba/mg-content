<?php

/**
 * This is the model base class for the table "server_profile".
 * DO NOT MODIFY THIS FILE! It is automatically generated by giix.
 * If any changes are necessary, you must set or override the required
 * property or method in class "ServerProfile".
 *
 * Columns in table "server_profile" available as properties of the model,
 * and there are no model relations.
 *
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property string $logo
 * @property string $description
 * @property integer $synchronized
 *
 */
abstract class BaseServerProfile extends GxActiveRecord {

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return 'server_profile';
	}

	public static function label($n = 1) {
		return Yii::t('app', 'ServerProfile|ServerProfiles', $n);
	}

	public static function representingColumn() {
		return 'name';
	}

	public function rules() {
		return array(
			array('name, url', 'required'),
			array('synchronized', 'numerical', 'integerOnly'=>true),
			array('name, url', 'length', 'max'=>128),
			array('description', 'safe'),
			array('logo, description, synchronized', 'default', 'setOnEmpty' => true, 'value' => null),
			array('id, name, url, logo, description, synchronized', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
		);
	}

	public function pivotModels() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('app', 'ID'),
			'name' => Yii::t('app', 'Name'),
			'url' => Yii::t('app', 'Url'),
			'logo' => Yii::t('app', 'Logo'),
			'description' => Yii::t('app', 'Description'),
			'synchronized' => Yii::t('app', 'Synchronized'),
		);
	}

	public function search() {
		$criteria = new CDbCriteria;

		$criteria->compare('id', $this->id);
		$criteria->compare('name', $this->name, true);
		$criteria->compare('url', $this->url, true);
		$criteria->compare('logo', $this->logo, true);
		$criteria->compare('description', $this->description, true);
		$criteria->compare('synchronized', $this->synchronized);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}