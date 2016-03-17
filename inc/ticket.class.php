<?php

/*
  ------------------------------------------------------------------------
  Surveyticket
  Copyright (C) 2012-2016 by the Surveyticket plugin Development Team.

  https://forge.glpi-project.org/projects/surveyticket
  ------------------------------------------------------------------------

  LICENSE

  This file is part of Surveyticket plugin project.

  Surveyticket plugin is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  Surveyticket plugin is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with Surveyticket plugin. If not, see <http://www.gnu.org/licenses/>.

  ------------------------------------------------------------------------

  @package   Surveyticket plugin
  @author    David Durieux
  @author    Infotel
  @copyright Copyright (c) 2012-2016 Surveyticket plugin team
  @license   AGPL License 3.0 or (at your option) any later version
  http://www.gnu.org/licenses/agpl-3.0-standalone.html
  @link      https://forge.glpi-project.org/projects/surveyticket
  @since     2012

  ------------------------------------------------------------------------
 */


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketTicket extends CommonDBTM {

   static $rightname = "plugin_surveyticket";

   /**
    * Get name of this type
    *
    * @return text name of this type by language of the user connected
    *
    * */
   static function getTypeName($nb = 0) {
      return _n('Ticket', 'Tickets', $nb);
   }

   static function emptyTicket(Ticket $ticket) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      }if (!empty($_REQUEST) && !empty($_REQUEST['_tickettemplates_id'])) {
         $ticket = new Ticket();
         $ticketTemplate = $ticket->getTicketTemplateToUse(false, $_REQUEST['type'], $_REQUEST['itilcategories_id'], $_REQUEST['entities_id']);
         if ($_REQUEST['_tickettemplates_id'] == $ticketTemplate->fields['id']) {
            self::setSessions($_REQUEST);
         }
      }
   }

   static function setSessions($input) {
      foreach ($input as $question => $answer) {
         if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
            $psAnswer = new PluginSurveyticketAnswer();
            $psQuestion = new PluginSurveyticketQuestion();
            $qid = str_replace("question", "", $question);
            $psQuestion->getFromDB($qid);

            if (is_array($answer)) {
               foreach ($answer as $val) {
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($val)) {
                     if (isset($input["text-" . $qid . "-" . $val])
                        AND $input["text-" . $qid . "-" . $val] != '') {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $input["text-" . $qid . "-" . $val];
                     } else {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = str_replace('\r', "", $val);
                     }
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $val;
                  }
               }
            } else {
               if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($answer)) {
                  if (isset($input["text-" . $qid . "-" . $answer])
                     AND $input["text-" . $qid . "-" . $answer] != '') {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = $input["text-" . $qid . "-" . $answer];
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = str_replace('\r', "", $answer);
                  }
               } else {
                  $_SESSION['glpi_plugin_surveyticket_ticket'][$qid] = $answer;
               }
            }
         }
      }
   }
   
   static function isSurveyTicket($type, $itilcategories_id, $entities_id, $interface) {
      // Load ticket template if available :
      $ticket                         = new Ticket();
      $ticketTemplate                 = $ticket->getTicketTemplateToUse(false, $type, $itilcategories_id, $entities_id);
      $psTicketTemplate               = new PluginSurveyticketTicketTemplate();
      $plugin_surveyticket_surveys_id = 0;
      if ($interface == 'central') {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_central`='1'"));
      } else {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_helpdesk`='1'"));
      }

      //if template exists
      if (isset($a_tickettemplates['plugin_surveyticket_surveys_id'])) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Return the questionnaire
    * @param type $type
    * @param type $itilcategories_id
    * @param type $entities_id
    * @return array(response, bloc)
    */
   static function getSurveyTicket($type, $itilcategories_id, $entities_id, $interface) {
      // If values are saved in session we retrieve it
      if (isset($_SESSION['glpi_plugin_surveyticket_ticket'])) {
         $session = $_SESSION['glpi_plugin_surveyticket_ticket'];
         unset($_SESSION['glpi_plugin_surveyticket_ticket']);
      } else {
         $session = array();
      }

      // Load ticket template if available :
      $ticket = new Ticket();
      $ticketTemplate = $ticket->getTicketTemplateToUse(false, $type, $itilcategories_id, $entities_id);
      $psTicketTemplate = new PluginSurveyticketTicketTemplate();
      $plugin_surveyticket_surveys_id = 0;
      if ($interface == 'central') {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_central`='1'"));
      } else {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_helpdesk`='1'"));
      }
      //if template exists
      if (isset($a_tickettemplates['plugin_surveyticket_surveys_id'])) {
         $psSurvey = new PluginSurveyticketSurvey();
         $psSurvey->getFromDB($a_tickettemplates['plugin_surveyticket_surveys_id']);
         if ($psSurvey->fields['is_active'] == 1) {
            $plugin_surveyticket_surveys_id = $a_tickettemplates['plugin_surveyticket_surveys_id'];
            $surveyTicket = new PluginSurveyticketTicket();
            $bloc = $surveyTicket->startSurvey($plugin_surveyticket_surveys_id, $session);
            unset($session);
            return array('response' => true, 'survey' => $bloc);
         }
      }
      unset($session);
      return array('response' => false);
   }

   function startSurvey($plugin_surveyticket_surveys_id, $session) {

      $psSurveyQuestion = new PluginSurveyticketSurveyQuestion();

      //list questions
      $a_questions = $psSurveyQuestion->find(
         "`plugin_surveyticket_surveys_id`='" . $plugin_surveyticket_surveys_id . "'", "`order`");

      $bloc = "<input name='plugin_surveyticket_surveys_id' type='hidden' value='" . $plugin_surveyticket_surveys_id . "'/>";
      foreach ($a_questions as $data) {
         $bloc .= $this->displaySurvey($data['plugin_surveyticket_questions_id'], $plugin_surveyticket_surveys_id, $session);
      }
      return $bloc;
   }

   /**
    * Block to a question
    * @param type $questions_id
    * @param type $plugin_surveyticket_surveys_id
    * @return string
    */
   function displaySurvey($questions_id, $plugin_surveyticket_surveys_id, $session) {

      $psQuestion = new PluginSurveyticketQuestion();

      if ($psQuestion->getFromDB($questions_id)) {
         //////////////                              Titre des questions alignés à gauche                                      /////////////
         $bloc = "<table class='tab_cadre' style='margin: 0;' width='700' >";
         $bloc .= "<tr class='tab_bg_1'>";
         $bloc .= "<th colspan='3' style='text-align: left;'>";
         if ($plugin_surveyticket_surveys_id == -1) {
            $bloc .= $psQuestion->fields['name'] . " ";
         } else {
            $surveyquestion = new PluginSurveyticketSurveyQuestion();
            $surveyquestion->getFromDBByQuery("WHERE `plugin_surveyticket_questions_id` = " . $psQuestion->fields['id'] . " AND `plugin_surveyticket_surveys_id` = " . $plugin_surveyticket_surveys_id);
            if ($surveyquestion->fields['mandatory']) {
               $bloc .= $psQuestion->fields['name'] . " <span class='red'>&nbsp;*&nbsp;</span>";
            } else {
               $bloc .= $psQuestion->fields['name'] . " ";
            }
         }
         $bloc .= Html::showToolTip($psQuestion->fields['comment'], array('display' => false));
         $bloc .= "</th>";
         $bloc .= "</tr>";
         //display answer for each question
         $array = $this->displayAnswers($questions_id, $session);
         $nb_answer = $array['count'];
         $bloc .= $array['bloc'];

         $bloc .= "</table>";
         $bloc .= $this->displayLink($questions_id, $session, $psQuestion, $nb_answer);

         $bloc .= "<br/><div id='nextquestion" . $questions_id . "'></div>";
         return $bloc;
      }
   }

   function displayLink($questions_id, $session, $psQuestion, $nb_answer) {
      global $CFG_GLPI;
      $bloc = "";
      //javascript for links between issues
      if ($psQuestion->fields['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion->fields['type'] == PluginSurveyticketQuestion::YESNO) {
         $event = array("click");
         $a_ids = array();
         //table id of all responses 
         for ($i = 0; $i < $nb_answer; $i++) {
            $a_ids[] = 'question' . $questions_id . "-" . $i;
         }
         $params = array("question" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "question" . $questions_id);
      } else if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type'])) {
         $event = array("change");
         $a_ids = "realquestion" . $questions_id;
         $params = array("realquestion" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "realquestion" . $questions_id);
      } else {
         $event = array("change");
         $a_ids = 'question' . $questions_id;
         $params = array("question" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "question" . $questions_id);
      }
      //script to detect if a change in response to a question
      if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type'])) {
         $bloc .= "<script type='text/javascript'>";
         $bloc .= Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids, false);
         $bloc .= "</script>";
      } elseif ($psQuestion->fields['type'] != PluginSurveyticketQuestion::CHECKBOX) {
         $bloc .= "<script type='text/javascript'>";
         if (!is_array($a_ids)) {
            $a_ids = array($a_ids);
         }
         foreach ($a_ids as $a_id) {
            $bloc .= Ajax::updateItemOnEventJsCode($a_id, "nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, array('change'), -1, -1, array(), FALSE);
         }
         $bloc .= "</script>";
         // Link to other issues loading the script
         if (!empty($session)) {
            $params['session'] = $session;
            $bloc .= "<script type='text/javascript'>";
            if ($psQuestion->fields['type'] == PluginSurveyticketQuestion::DROPDOWN) {
               //dropdown load on the issue
               $bloc .= Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, "question" . $questions_id, false);
            } elseif ($psQuestion->fields['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion->fields['type'] == PluginSurveyticketQuestion::YESNO) {
               //load on the selected response 
               $psAnswer = new PluginSurveyticketAnswer();
               $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $psQuestion->fields['id']);
               $i = 0;
               foreach ($result as $data) {
                  if (!empty($session[$questions_id]) && array_key_exists($data['id'], $session[$questions_id])) {
                     $bloc .= Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids[$i], false);
                  }
                  $i++;
               }
            }
            $bloc .= "</script>";
         }
      }
      return $bloc;
   }

   /**
    * Response for each question
    * @param type $questions_id
    * @return type
    */
   function displayAnswers($questions_id, $session) {

      $psQuestion = new PluginSurveyticketQuestion();
      $psAnswer = new PluginSurveyticketAnswer();

      $a_answers = $psAnswer->find("`plugin_surveyticket_questions_id`='" . $questions_id . "'");

      $psQuestion->getFromDB($questions_id);
      $bloc = "";
      switch ($psQuestion->fields['type']) {
         case PluginSurveyticketQuestion::DROPDOWN:
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<td colspan='2'>";
            $bloc .= "<select name='question" . $questions_id . "' id='question" . $questions_id . "' >";
            $bloc .= "<option>" . Dropdown::EMPTY_VALUE . "</option>";
            foreach ($a_answers as $data_answer) {
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<option value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               } else {
                  $bloc .= "<option selected value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               }
            }
            $bloc .= "</select>";
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::CHECKBOX :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               $bloc .= "<tr class='tab_bg_1'>";
               $bloc .= "<td width='40' >";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' />";
               } else {
                  $bloc .= "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked />";
               }
               $bloc .= "</td>";
               $bloc .= "<td>";
               $bloc .= $psAnswer->getAnswer($data_answer);
               $bloc .= "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               $bloc .= "</tr>";
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::RADIO :
         case PluginSurveyticketQuestion::YESNO :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               $bloc .= "<tr class='tab_bg_1'>";
               $bloc .= "<td width='40'>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' />";
               } else {
                  $bloc .= "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked/>";
               }
               $bloc .= "</td>";
               $bloc .= "<td>";
               $bloc .= $psAnswer->getAnswer($data_answer);
               $bloc .= "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               $bloc .= "</tr>";
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::DATE :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id][$data_answer])) {
               $bloc .= Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id, "display" => false));
            } else {
               $bloc .= Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id, "display" => false, 'value' => $session[$questions_id]));
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::INPUT :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id])) {
               $bloc .= '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="" size="100" />';
            } else {
               $bloc .= '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="' . $session[$questions_id] . '" size="100" />';
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::TEXTAREA :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id])) {
               $bloc .= '<textarea name="question' . $questions_id . '"  cols="90" rows="4"></textarea>';
            } else {
               $bloc .= '<textarea name="question' . $questions_id . '"  cols="90" rows="4" >' . $session[$questions_id] . '</textarea>';
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;
      }
      return array("count" => count($a_answers), "bloc" => $bloc);
   }

   function displayAnswertype($type, $name, $session) {

      $bloc = "<td>";
      if ($type != '') {
         //echo "<tr class='tab_bg_1'>";
         switch ($type) {

            case 'shorttext':
               if ($session == NULL) {
                  $bloc .= "<input type='text' name='" . $name . "' value='' size='71'/>";
               } else {
                  $bloc .= "<input type='text' name='" . $name . "' value='" . $session . "' size='71'/>";
               }
               break;

            case 'longtext':
               if ($session == NULL) {
                  $bloc .= "<textarea name='" . $name . "' cols='100' rows='4'></textarea>";
               } else {
                  $bloc .= "<textarea name='" . $name . "' cols='100' rows='4' value='" . $session . "'></textarea>";
               }
               break;

            case 'date':
               if ($session == NULL) {
                  $bloc .= Html::showDateTimeField($name, array("display" => false));
               } else {
                  $bloc .= Html::showDateTimeField($name, array("display" => false, 'value' => $session));
               }
               break;

            case 'number':
               if ($session == NULL) {
                  $bloc .= Dropdown::showNumber($name, array("display" => false));
               } else {
                  $bloc .= Dropdown::showNumber($name, array("display" => false, 'value' => $session));
               }
               break;
         }
      }
      $bloc .= "</td>";
      return $bloc;
   }

   function displayOK() {
      $bloc = "<table class='tab_cadre_fixe'>";

      $bloc .= "<tr class='tab_bg_1'>";
      $bloc .= "<th align='center'>";
      $bloc .= "<input type='submit' class='submit' value='" . __('Post') . "'/>";
      $bloc .= "</th>";
      $bloc .= "</tr>";

      $bloc .= "</table>";
      Html::closeForm();
   }

   static function checkMandatoryFields(Ticket $ticket) {
      $msg     = array();
      $checkKo = false;
      $surveyquestion = new PluginSurveyticketSurveyQuestion();
      $a_questions    = $surveyquestion->find("`plugin_surveyticket_surveys_id`='" . $ticket->input['plugin_surveyticket_surveys_id'] . "'", "`order`");
      foreach ($a_questions as $data) {
         $reponse = self::checkQuestion(array('msg'=> $msg, 'checkKo' => $checkKo, 'data' => $data, 'ticket' => $ticket->input));
         $msg = $reponse['msg'];
         $checkKo = $reponse['checkKo'];
         
      }
      if ($checkKo) {
         Session::addMessageAfterRedirect(sprintf(__("Mandatory questions are not filled. Please correct: %s", 'surveyticket'), implode(', ', $msg)), false, ERROR);
         return false;
      }
      return true;
   }
   
   static function checkQuestion($param){
      $data = $param['data'];
      $msg = $param['msg'];
      $checkKo = $param['checkKo'];
      $ticket = $param['ticket'];
      if (isset($data['mandatory']) && $data['mandatory']) {
            $psQuestion = new PluginSurveyticketQuestion();
            $psQuestion->getFromDB($data['plugin_surveyticket_questions_id']);
            
            if (!isset($ticket['question' . $psQuestion->fields['id']])) {
               $msg[]   = $psQuestion->fields['name'];
               $checkKo = true;
            }
         }
         $psAnswer = new PluginSurveyticketAnswer();
         $result   = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $data['plugin_surveyticket_questions_id']);
         foreach ($result as $data_answer) {
            if ($data_answer['link'] > 0) {
               self::checkQuestion(array('msg'=> $msg, 
                                         'checkKo' => $checkKo, 
                                         'ticket' => $ticket,
                                         'data' => array('plugin_surveyticket_questions_id' => $data_answer['link'], 
                                                         'old_plugin_surveyticket_questions_id' => $data['plugin_surveyticket_questions_id'])));
            }
         }
         return array('msg'=> $msg, 'checkKo' => $checkKo);
   }

   static function preAddTicket(Ticket $ticket) {
      if($_SESSION['glpiactiveprofile']['interface'] == 'central'){
         $response = self::isSurveyTicket($ticket->fields['type'], $ticket->fields['itilcategories_id'], $ticket->fields['entities_id'], $_SESSION['glpiactiveprofile']['interface']);
      }else{
         $response = self::isSurveyTicket($ticket->input['type'], $ticket->input['itilcategories_id'], $ticket->input['entities_id'], $_SESSION['glpiactiveprofile']['interface']);
      }
      if (!$response) {
         return true;
      }
      self::setSessions($ticket->input);
      if (self::checkMandatoryFields($ticket)) {
         //Recovery of the survey to put in the content of the ticket
         $psQuestion  = new PluginSurveyticketQuestion();
         $psAnswer    = new PluginSurveyticketAnswer();
         $description = '';
         foreach ($ticket->input as $question => $answer) {
            if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
               $psQuestion->getFromDB(str_replace("question", "", $question));

               if (is_array($answer)) {
                  // Checkbox
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $psQuestion->fields['name'] . "\n";
                  foreach ($answer as $answers_id) {
                     if ($psAnswer->getFromDB($answers_id)) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $psAnswer->fields['name'] . "\n";
                        $qid = str_replace("question", "", $question);
                        if (isset($ticket->input["text-" . $qid . "-" . $answers_id])
                           AND $ticket->input["text-" . $qid . "-" . $answers_id] != '') {
                           $description .= "Texte : " . $ticket->input["text-" . $qid . "-" . $answers_id] . "\n";
                        }
                     }
                  }
                  $description .= "\n";
                  unset($ticket->input[$question]);
               } else {
                  $real = 0;
                  if (isset($ticket->input['realquestion' . (str_replace("question", "", $question))]) && $ticket->input['realquestion' . (str_replace("question", "", $question))] != '') {
                     $realanswer = $answer;
                     $answer     = $ticket->input['realquestion' . str_replace("question", "", $question)];
                     $real       = 1;
                  }
                  $description .= "===========================================================================\n";
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $psQuestion->fields['name'] . "\n";
                  //check if it is an id
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($answer)) {
                     if ($real == 1) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $realanswer . "\n";
                     } else {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $psAnswer->fields['name'] . "\n";
                     }
                     $qid = str_replace("question", "", $question);
                     if (isset($ticket->input["text-" . $qid . "-" . $answer])
                        AND $ticket->input["text-" . $qid . "-" . $answer] != '') {
                        $description .= __('Text', 'surveyticket') . " : " . $ticket->input["text-" . $qid . "-" . $answer] . "\n";
                     }
                     $description .= "\n";
                     unset($ticket->input[$question]);
                  } else {
                     $description .= __('Text', 'surveyticket') . " : " . str_replace('\r', "", $answer) . "\n";
                     $description .= "\n";
                     unset($ticket->input[$question]);
                  }
               }
            }
            if ($description != '') {
               $ticket->input['content'] = addslashes($description);
            }
         }
      } else {
         $ticket->input['content'] = '';
         return false;
      }

      return $ticket;
   }

   /**
    * Empty the session variable to not refill the old values
    * @param Ticket $ticket
    */
   static function postAddTicket(Ticket $ticket) {
      if (isset($_SESSION['glpi_plugin_surveyticket_ticket'])) {
         unset($_SESSION['glpi_plugin_surveyticket_ticket']);
      }
   }
}

?>
