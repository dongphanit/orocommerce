<?php

namespace Oro\Bundle\ActionBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Model\ActionManager;

class WidgetController extends Controller
{
    const DEFAULT_DIALOG_TEMPLATE = 'OroActionBundle:Widget:widget/form.html.twig';

    /**
     * @Route("/buttons", name="oro_action_widget_buttons")
     * @Template()
     *
     * @return array
     */
    public function buttonsAction()
    {
        $context = $this->get('oro_action.helper.context')->getContext();

        return [
            'actions' => $this->getActionManager()->getActions($context),
            'context' => $context
        ];
    }

    /**
     * @Route("/form/{actionName}", name="oro_action_widget_form")
     *
     * @param Request $request
     * @param string $actionName
     * @return Response
     */
    public function formAction(Request $request, $actionName)
    {
        /** @var Form $form */
        $form = $this->get('oro_action.form_manager')->getActionForm($actionName);
        $params = [];

        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                $context = $this->getActionManager()->execute($actionName);

                $params['response'] = $context->getRedirectUrl() ? ['redirectUrl' => $context->getRedirectUrl()] : [];
            }
        }
        $params['form'] = $form->createView();
        
        $frontendOptions = $action->getDefinition()->getFrontendOptions();
        $template = !empty($frontendOptions['dialog_template'])
            ? $frontendOptions['dialog_template']
            : self::DEFAULT_DIALOG_TEMPLATE;

        return $this->render($template, $params);
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->get('oro_action.manager');
    }
}
