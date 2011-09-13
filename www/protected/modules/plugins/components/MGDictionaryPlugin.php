<?php

/**
 * This is the base implementation of a dictionary plug-in 
 */

class MGDictionaryPlugin extends MGPlugin {  
  function init() {
    parent::init();
  }
  
  /**
   * This handler allows dictionary plugins to contribute to the game submissions parsing
   * 
   * @param object $game the game object
   * @param object $game_model the active model of the current game
   * @return boolean true if parsing was successful
   */
  function parseSubmission(&$game, &$game_model) {
    return true;
  }
  
  /**
   * With help of this method you can add further elements to the words to avoid array
   * 
   * See MGTags::saveTags for the data structure of the $tags array
   * 
   * The format of &$wordsToAvoid is 
   * array(
   *  image_id = array(
   *    tag_id => array(
   *      "tag" => "tag" // tag == the word to avoid
   *      "total" => "SUM(tu.weight)" // this is just additional info provided by MGTags::getTagsByWeightThreshold(...)
   *    )
   *    ...
   *  )
   *  ...
   * )
   * @param array $tags the words to avoid generated by MGTags::getTagsByWeightThreshold(...)
   * @param array $used_images array of image_ids that will be used in this turn 
   * @param object $game the object representing the current game 
   * @param object $game_model the current games model
   * @return array tags that have been found
   */
  function lookup($tags, &$used_images, &$game, &$game_model) {
    return array();
  }
  
  /**
   * comment xxx
   */
  function setWeights(&$game, &$game_model, $tags) {
    return $tags;
  }
  
  /**
   * With help of this method you can add further elements to the words to avoid array
   * 
   * See MGTags::saveTags for the data structure of the $tags array
   * 
   * The format of &$wordsToAvoid is 
   * array(
   *  image_id = array(
   *    tag_id => array(
   *      "tag" => "tag" // tag == the word to avoid
   *      "total" => "SUM(tu.weight)" // this is just additional info provided by MGTags::getTagsByWeightThreshold(...)
   *    )
   *    ...
   *  )
   *  ...
   * )
   * @param array $wordsToAvoid the words to avoid generated by MGTags::getTagsByWeightThreshold(...)
   * @param array $used_images array of image_ids that will be used in this turn 
   * @param object $game the object representing the current game 
   * @param object $game_model the current games model
   * @param array $tags the previous turn's submitted tags
   */
  function wordsToAvoid(&$wordsToAvoid, &$used_images, &$game, &$game_model, &$tags) {}
  
  /**
   * Placeholder for future functionality
   */
  function cleanUp() {}
  
  /**
   * Placeholder for future functionality
   */
  function expand() {} 
  
}
