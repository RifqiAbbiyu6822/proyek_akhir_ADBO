<?php
class Validator {
    private $errors = [];
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function validate($rules) {
        foreach ($rules as $field => $rule) {
            $value = $this->data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $this->addError($field, ucfirst($field) . ' is required');
                continue;
            }
            
            if (!empty($value)) {
                $ruleArray = explode('|', $rule);
                foreach ($ruleArray as $singleRule) {
                    if ($singleRule === 'required') continue;
                    
                    if (strpos($singleRule, 'min:') === 0) {
                        $min = substr($singleRule, 4);
                        if (strlen($value) < $min) {
                            $this->addError($field, ucfirst($field) . " must be at least {$min} characters");
                        }
                    }
                    
                    if (strpos($singleRule, 'max:') === 0) {
                        $max = substr($singleRule, 4);
                        if (strlen($value) > $max) {
                            $this->addError($field, ucfirst($field) . " must not exceed {$max} characters");
                        }
                    }
                    
                    if ($singleRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, 'Invalid email format');
                    }
                    
                    if ($singleRule === 'date' && !strtotime($value)) {
                        $this->addError($field, 'Invalid date format');
                    }
                    
                    if ($singleRule === 'numeric' && !is_numeric($value)) {
                        $this->addError($field, ucfirst($field) . ' must be a number');
                    }
                }
            }
        }
        
        return empty($this->errors);
    }

    public function addError($field, $message) {
        $this->errors[$field][] = $message;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        if (!empty($this->errors)) {
            $firstField = array_key_first($this->errors);
            return $this->errors[$firstField][0];
        }
        return null;
    }
} 