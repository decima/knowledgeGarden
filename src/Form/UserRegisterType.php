<?php

namespace App\Form;

use App\Services\Configuration\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserRegisterType extends AbstractType
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker, #[CurrentUser] private User $user)
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('clearPassword', PasswordType::class, ['required' => false, 'label' => 'Password']);
        if ($this->authorizationChecker->isGranted("ROLE_ADMIN")) {
            $builder->add('permissions', ChoiceType::class, [
                'choices' => [
                    "Read" => "ROLE_READ",
                    "Write" => "ROLE_WRITE",
                    "Administrate" => "ROLE_ADMIN",
                ],
                'multiple' => true,
                'expanded' => true,
                'choice_attr' => function ($choice, string $key, mixed $value) {
                    return ['class' => 'checkbox'];
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
