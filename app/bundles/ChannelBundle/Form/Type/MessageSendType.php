<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class MessageSendType.
 */
class MessageSendType extends AbstractType
{
    protected $router;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'marketingMessage',
            'message_list',
            [
                'label'      => 'mautic.channel.send.selectmessages',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.channel.choose.messages_descr',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.channel.choosemessage.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_message_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newMessageButton',
                'button',
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.channel.create.new.message',
                ]
            );

            // create button edit email
            $windowUrlEdit = $this->router->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'emailId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editMessageButton',
                'button',
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-edit',
                    ],
                    'label' => 'mautic.email.send.edit.email',
                ]
            );

            // create button preview email
            $windowUrlPreview = $this->router->generate('mautic_email_preview', ['objectId' => 'emailId']);

            $builder->add(
                'previewMessageButton',
                'button',
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlPreview.'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-external-link',
                    ],
                    'label' => 'mautic.email.send.preview.email',
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'message_send';
    }
}
