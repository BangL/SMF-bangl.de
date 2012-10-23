<?php
// Version: 3.0; CustomAction

function template_view_custom_action()
{
	global $context;
	
	switch ($context['action']['action_type'])
	{
	// HTML.
	case 0:
		echo $context['action']['body'];
		break;
	// BBC.
	case 1:
		echo $context['action']['body'];
		break;
	// PHP.
	case 2:
		eval($context['action']['body']);
		break;
	}
}
?>