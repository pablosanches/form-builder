<?php

namespace FormBuilder;

use FormBuilder\RuntimeException;

/**
 * Class Builder
 *
 * @package FormBuilder
 * @author Pablo Sanches <sanches.webmaster@gmail.com>
 * @copyright Copyright 2017 (c) Pablo Sanches Software Development Inc.
 */
class Builder
{
	private $defaults_methods = array(
		'post', 
		'get'
	);

	private $defaults_enctypes = array(
		'application/x-www-form-urlencoded', 
		'multipart/form-data'
	);

	private $defaults_markups = array(
		'html',
		'xhtml'
	);

	private $inputs = array();

	private $form = array();

	private $has_submit = false;

	/**
	 * Constructor function to set form action and attributes
	 *
	 * @param string $action
	 * @param bool   $args
	 */
	public function __construct($action = '', $args = false)
	{
		$defaults = array(
			'action' 		=> $action,
			'method' 		=> 'post',
			'enctype' 		=> 'application/x-www-form-urlencoded',
			'class' 		=> array(),
			'id' 			=> '',
			'markup' 		=> 'html',
			'novalidate' 	=> false,
			'add_nonce' 	=> false,
			'add_honeypot' 	=> true,
			'form_element' 	=> true,
			'add_submit' 	=> true
		);

		if ($args) {
			$settings = array_merge($defaults, $args);
		} else {
			$settings = $defaults;
		}

		foreach ($settings as $key => $value) {
			if (!$this->set($key, $value)) {
				$this->set($key, $defaults[$key]);
			}
		}
	}

	/**
	 * Validate and set form
	 *
	 * @param string        $key A valid key; switch statement ensures validity
	 * @param string | bool $val A valid value; validated for each key
	 *
	 * @return bool
	 */
	public function set($key, $value) {
		switch ($key) {
			case 'action':

			break;

			case 'method':
				if (!in_array($value, $this->defaults_methods)) {
					return false;
				}
			break;

			case 'enctype':
				if (!in_array($value, $this->defaults_enctypes)) {
					return false;
				}
			break;

			case 'markup':
				if (!in_array($value, $this->defaults_markups)) {
					return false;
				}
			break;

			case 'class':
			case 'id':
				
			break;

			case 'novalidate':
			case 'add_honeypot':
			case 'form_element':
			case 'add_submit':
				if (!is_bool($value)) {
					return false;
				}
			break;

			case 'add_nonce':
				if (!is_string($value) && !is_bool($value)) {
					return false;
				}
			break;

			default:
				return false;
			break;
		}

		$this->form[$key] = $value;

		return true;
	}

	/**
	 * Add an input field to the form for outputting later
	 *
	 * @param string $label
	 * @param string $args
	 * @param string $slug
	 */
	public function addInput(
		$label, 
		$args = '', 
		$slug = ''
	) {
		if (empty($args)) {
			$args = array();
		}

		if (empty($slug)) {
			$slug = $this->makeSlug($label);
		}

		$defaults = array(
			'type'             => 'text',
			'name'             => $slug,
			'id'               => $slug,
			'label'            => $label,
			'value'            => '',
			'placeholder'      => '',
			'class'            => array(),
			'min'              => '',
			'max'              => '',
			'step'             => '',
			'autofocus'        => false,
			'checked'          => false,
			'selected'         => false,
			'required'         => false,
			'add_label'        => true,
			'options'          => array(),
			'wrap_tag'         => 'div',
			'wrap_class'       => array('form_field_wrap'),
			'wrap_id'          => '',
			'wrap_style'       => '',
			'before_html'      => '',
			'after_html'       => '',
			'request_populate' => true
		);

		$this->inputs[$slug] = array_merge($defaults, $args);

		return $this;
	}

	/**
	 * Add multiple inputs to the input queue
	 *
	 * @param $arr
	 *
	 * @return bool
	 */
	public function addInputs(array $arr) {

		if (!is_array($arr)) {
			return false;
		}

		foreach ($arr as $field) {
			$this->addInput(
				$field[0], 
				isset( $field[1] ) ? $field[1] : '',
				isset( $field[2] ) ? $field[2] : ''
			);
		}

		return true;
	}

	/**
	 * Build the HTML for the form based on the input queue
	 *
	 * @param bool $echo Should the HTML be echoed or returned?
	 *
	 * @return string
	 */
	public function buildForm($echo = true)
	{
		$output = '';

		if ($this->form['form_element']) {
			$output .= '<form method="' . $this->form['method'] . '"';

			// ENCTYPE
			if (!empty($this->form['enctype'])) {
				$output .= ' enctype="' . $this->form['enctype'] . '"';
			}


			// ACTION
			if (!empty($this->form['action'])) {
				$output .= ' action="' . $this->form['action'] . '"';
			}

			// ID
			if (!empty($this->form['id'])) {
				$output .= ' id="' . $this->form['id'] . '"';
			}

			// CLASS
			if (count($this->form['class']) > 0) {
				$output .= $this->outputClass($this->form['class']);
			}


			// NOVALIDATE
			if ($this->form['novalidate']) {
				$output .= ' novalidate';
			}

			$output .= '>';
		}

		// Add honeypot anti-spam field
		if ($this->form['add_honeypot']) {
			$this->add_input( 'Leave blank to submit', array(
				'name'             => 'honeypot',
				'slug'             => 'honeypot',
				'id'               => 'form_honeypot',
				'wrap_tag'         => 'div',
				'wrap_class'       => array('form_field_wrap', 'hidden'),
				'wrap_id'          => '',
				'wrap_style'       => 'display: none',
				'request_populate' => false
			));
		}

		// Iterate through the input queue and add input HTML
		foreach ($this->inputs as $value) {
			$min_max_range = $element = $end = $attr = $field = $label_html = '';

			// Automatic population of values using $_REQUEST data
			if ($value['request_populate'] && isset($_REQUEST[$value['name']])) {
				// Can this field be populated directly?
				$allowedTypes = array(
					'html', 
					'title', 
					'radio', 
					'checkbox', 
					'select', 
					'submit'
				);

				if (!in_array($value['type'], $allowedTypes)) {
					$value['value'] = $_REQUEST[$value['name']];
				}
			}

			// Automatic population for checkboxes and radios
			if (
				$value['request_populate'] &&
				($value['type'] == 'radio' || $value['type'] == 'checkbox') &&
				empty($value['options'])
			) {
				$value['checked'] = isset($_REQUEST[$value['name']]) 
				? true 
				: $value['checked'];
			}

			switch ($value['type']) {
				case 'html':
					$element = '';
					$end = $value['label'];
				break;

				case 'title':
					$element = '';
					$end = '<h3>' . $value['label'] . '</h3>';
				break;

				case 'textarea':
					$element = 'textarea';
					$end     = '>' . $value['value'] . '</textarea>';
				break;

				case 'select':
					$element = 'select';
					$end     .= '>';

					foreach ($value['options'] as $key => $opt) {
						$opt_insert = '';

						if (
							// Is this field set to automatically populate?
							$value['request_populate'] &&
							// Do we have $_REQUEST data to use?
							isset($_REQUEST[$value['name']]) &&
							// Are we currently outputting the selected value?
							$_REQUEST[$value['name']] === $key
						) {
							$opt_insert = ' selected';
						// Does the field have a default selected value?
						} else if ($value['selected'] === $key) {
							$opt_insert = ' selected';
						}
						$end .= '<option value="' . $key . '"' . $opt_insert . '>' . $opt . '</option>';
					}
					$end .= '</select>';
				break;

				case 'radio':
				case 'checkbox':
					// Special case for multiple check boxes
					if ( count( $value['options'] ) > 0 ) {
						$element = '';
						foreach ($value['options'] as $key => $opt) {
							$slug = $this->_make_slug( $opt );
							$end .= sprintf(
								'<input type="%s" name="%s[]" value="%s" id="%s"',
								$value['type'],
								$value['name'],
								$key,
								$slug
							);

							if (
								// Is this field set to automatically populate?
								$value['request_populate'] &&
								// Do we have $_REQUEST data to use?
								isset($_REQUEST[$value['name']]) &&
								// Is the selected item(s) in the $_REQUEST data?
								in_array($key, $_REQUEST[$value['name']])
							) {
								$end .= ' checked';
							}
							$end .= $this->field_close();
							$end .= ' <label for="' . $slug . '">' . $opt . '</label>';
						}

						$label_html = '<div class="checkbox_header">' . $value['label'] . '</div>';
					}
				break;
				
				default :
					$element = 'input';
					$end .= ' type="' . $value['type'] . '" value="' . $value['value'] . '"';
					$end .= $value['checked'] ? ' checked' : '';
					$end .= $this->field_close();
				break;
			}

			// Added a submit button, no need to auto-add one
			if ($value['type'] === 'submit') {
				$this->has_submit = true;
			}

			// Special number values for range and number types
			if ($value['type'] === 'range' || $value['type'] === 'number') {
				$min_max_range .= !empty($value['min']) 
					? ' min="' . $value['min'] . '"' 
					: '';

				$min_max_range .= !empty($value['max']) 
					? ' max="' . $value['max'] . '"' 
					: '';

				$min_max_range .= !empty($value['step']) 
					? ' step="' . $value['step'] . '"' 
					: '';
			}

			// Add an ID field, if one is present
			$id = !empty($value['id']) ? ' id="' . $value['id'] . '"' : '';

			// Output classes
			$class = $this->outputClass($value['class']);

			// Special HTML5 fields, if set
			$attr .= $value['autofocus'] ? ' autofocus' : '';
			$attr .= $value['checked'] ? ' checked' : '';
			$attr .= $value['required'] ? ' required' : '';

			// Build the label
			if (!empty($label_html)) {
				$field .= $label_html;
			} else if (
				$value['add_label'] && 
				!in_array($value['type'], array('hidden', 'submit', 'title', 'html'))
			) {
				if ($value['required']) {
					$value['label'] .= ' <strong>*</strong>';
				}

				$field .= '<label for="' . $value['id'] . '">' . $value['label'] . '</label>';
			}

			// An $element was set in the $value['type'] switch statement above so use that
			if (!empty($element)) {
				if ($value['type'] === 'checkbox') {
					$field = '
					<' . $element . $id . ' name="' . $value['name'] . '"' . $min_max_range . $class . $attr . $end .
					         $field;
				} else {
					$field .= '
					<' . $element . $id . ' name="' . $value['name'] . '"' . $min_max_range . $class . $attr . $end;
				}
			// Not a form element
			} else {
				$field .= $end;
			}

			// Parse and create wrap, if needed
			if ($value['type'] != 'hidden' && $value['type'] != 'html') :
				$wrap_before = $value['before_html'];
				if (!empty( $value['wrap_tag'])) {
					$wrap_before .= '<' . $value['wrap_tag'];
					$wrap_before .= count( $value['wrap_class'] ) > 0 ? $this->_output_classes( $value['wrap_class'] ) : '';
					$wrap_before .= ! empty( $value['wrap_style'] ) ? ' style="' . $value['wrap_style'] . '"' : '';
					$wrap_before .= ! empty( $value['wrap_id'] ) ? ' id="' . $value['wrap_id'] . '"' : '';
					$wrap_before .= '>';
				}
				
				$wrap_after = $value['after_html'];

				if (!empty($value['wrap_tag'])) {
					$wrap_after = '</' . $value['wrap_tag'] . '>' . $wrap_after;
				}

				$output .= $wrap_before . $field . $wrap_after;
			else :
				$output .= $field;
			endif;
		}

		// Auto-add submit button
		if (!$this->has_submit && $this->form['add_submit']) {
			$output .= '<div class="form_field_wrap"><input type="submit" value="Submit" name="submit"></div>';
		}

		// Close the form tag if one was added
		if ($this->form['form_element']) {
			$output .= '</form>';
		}

		// Print or return output
		if ($echo) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	* Create a slug from a label name
	*
	* @return string
	*/
	public function fieldClose() {
		return $this->form['markup'] === 'xhtml' ? ' />' : '>';
	}

	/**
	* Easy way to auto-close fields, if necessary
	*
	* @return string
	*/
	private function makeSlug($string) {

		$result = '';

		$result = str_replace('"', '', $string);
		$result = str_replace("'", '', $result);
		$result = str_replace('_', '-', $result);
		$result = preg_replace('~[\W\s]~', '-', $result);

		$result = strtolower($result);

		return $result;
	}

	/**
	* Parses and builds the classes in multiple places
	*
	* @return string
	*/
	private function outputClass($classes) {
		$output = '';
		
		if (is_array($classes) && count($classes) > 0) {
			$output .= ' class="';
			foreach ($classes as $class) {
				$output .= $class . ' ';
			}

			$output .= '"';
		} else if (is_string($classes)) {
			$output .= ' class="' . $classes . '"';
		}

		return $output;
	}
}