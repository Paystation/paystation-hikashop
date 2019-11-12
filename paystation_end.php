<?php

defined('_JEXEC') or die('Restricted access');
?><div class="hikashop_paystation_end" id="hikashop_paystation_end">
	<span id="hikashop_paystation_end_message" class="hikashop_paystation_end_message">
		<?php echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X',$this->payment_name).'<br/>'. JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED');?>
	</span>
	<span id="hikashop_paystation_end_spinner" class="hikashop_paystation_end_spinner">
		<img src="<?php echo HIKASHOP_IMAGES.'spinner.gif';?>" />
	</span>
	<br/>
	<form id="hikashop_paystation_form" name="hikashop_paystation_form" action="<?php echo $this->url; ?>" method="post">
		<div id="hikashop_paystation_end_image" class="hikashop_paystation_end_image">
			<input id="hikashop_paystation_button" type="submit" class="btn btn-primary" value="<?php echo JText::_('PAY_NOW');?>" name="" alt="<?php echo JText::_('PAY_NOW');?>" />
		</div>
		<?php
			foreach( $this->vars as $name => $value ) {
				echo '<input type="hidden" name="'.$name.'" value="'.htmlspecialchars((string)$value).'" />';
			}
			$doc =& JFactory::getDocument();
			$doc->addScriptDeclaration("window.addEvent('domready', function() {document.getElementById('hikashop_paystation_form').submit();});");
			JRequest::setVar('noform',1);
		?>
	</form>
</div>
