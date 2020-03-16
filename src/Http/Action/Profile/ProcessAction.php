<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2020 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Http\Action\Profile;

use HTMLPurifier;
use OpenCFP\Domain\Model;
use OpenCFP\Domain\Services;
use OpenCFP\Http\Form\SignupForm;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing;
use Twig\Environment;

final class ProcessAction
{
    /**
     * @var Services\Authentication
     */
    private $authentication;

    /**
     * @var HTMLPurifier
     */
    private $purifier;

    /**
     * @var Services\ProfileImageProcessor
     */
    private $profileImageProcessor;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var Routing\Generator\UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        Services\Authentication $authentication,
        HTMLPurifier $purifier,
        Services\ProfileImageProcessor $profileImageProcessor,
        Environment $twig,
        Routing\Generator\UrlGeneratorInterface $urlGenerator
    ) {
        $this->authentication        = $authentication;
        $this->purifier              = $purifier;
        $this->profileImageProcessor = $profileImageProcessor;
        $this->twig                  = $twig;
        $this->urlGenerator          = $urlGenerator;
    }

    public function __invoke(HttpFoundation\Request $request): HttpFoundation\Response
    {
        $userId = $this->authentication->user()->getId();

        if ((string) $userId !== $request->request->get('id')) {
            $request->getSession()->set('flash', [
                'type'  => 'error',
                'short' => 'Error',
                'ext'   => "You cannot edit someone else's profile",
            ]);

            $url = $this->urlGenerator->generate('dashboard');

            return new HttpFoundation\RedirectResponse($url);
        }

        $formData = $this->getFormData($request);

        if ($request->files->get('speaker_photo') != null) {
            $formData['speaker_photo'] = $request->files->get('speaker_photo');
        }

        $form = new SignupForm(
            $formData,
            $this->purifier
        );

        $isValid = $form->validateAll('update');

        if ($isValid) {
            $sanitizedData = $this->transformSanitizedData($form->getCleanData());

            if (isset($formData['speaker_photo'])) {
                $sanitizedData['photo_path'] = $this->profileImageProcessor->process($formData['speaker_photo']);
            }

            unset($sanitizedData['speaker_photo']);

            Model\User::find($userId)->update($sanitizedData);

            $url = $this->urlGenerator->generate('dashboard');

            return new HttpFoundation\RedirectResponse($url);
        }

        $request->getSession()->set('flash', [
            'type'  => 'error',
            'short' => 'Error',
            'ext'   => \implode('<br>', $form->getErrorMessages()),
        ]);

        $content = $this->twig->render('user/edit.twig', \array_merge($formData, [
            'formAction' => $this->urlGenerator->generate('user_update'),
            'buttonInfo' => 'Update Profile',
            'id'         => $userId,
            'flash'      => $request->getSession()->get('flash'),
        ]));

        return new HttpFoundation\Response($content);
    }

    private function getFormData(HttpFoundation\Request $request): array
    {
        return [
            'email'            => $request->request->get('email'),
            'user_id'          => $request->request->get('id'),
            'first_name'       => $request->request->get('first_name'),
            'last_name'        => $request->request->get('last_name'),
            'company'          => $request->request->get('company'),
            'wporg'            => $request->request->get('wporg'),
            'twitter'          => $request->request->get('twitter'),
            'joindin_username' => $request->request->get('joindin_username'),
            'url'              => $request->request->get('url'),
            'airport'          => $request->request->get('airport'),
            'transportation'   => $request->request->getInt('transportation'),
            'hotel'            => $request->request->getInt('hotel'),
            'speaker_info'     => $request->request->get('speaker_info') ?: null,
            'speaker_bio'      => $request->request->get('speaker_bio') ?: null,
            'speaker_exp'      => $request->request->get('speaker_exp') ?: null,
        ];
    }

    /**
     * Transforms the sanitized data array to be used by our User Model for updates
     *
     * @param array $sanitizedData
     *
     * @return array
     */
    private function transformSanitizedData(array $sanitizedData): array
    {
        $sanitizedData['wporg'] = \preg_replace(
            '/^@/',
            '',
            $sanitizedData['wporg']
        );

        $sanitizedData['twitter'] = \preg_replace(
            '/^@/',
            '',
            $sanitizedData['twitter']
        );

        $sanitizedData['bio']              = $sanitizedData['speaker_bio'];
        $sanitizedData['exp']              = $sanitizedData['speaker_exp'];
        $sanitizedData['info']             = $sanitizedData['speaker_info'];
        $sanitizedData['id']               = $sanitizedData['user_id'];
        $sanitizedData['has_made_profile'] = 1;

        unset(
            $sanitizedData['speaker_bio'],
            $sanitizedData['speaker_exp'],
            $sanitizedData['speaker_info'],
            $sanitizedData['user_id']
        );

        return $sanitizedData;
    }
}
