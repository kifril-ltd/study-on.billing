<?php

namespace App\Command;

use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\Twig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'payment:ending:notification',
    description: 'Send notifications about expired courses',
)]
class PaymentEndingNotificationCommand extends Command
{
    private Twig $twig;
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private MailerInterface $mailer;

    public function __construct(
        Twig                  $twig,
        TransactionRepository $transactionRepository,
        UserRepository        $userRepository,
        MailerInterface       $mailer,
        string                $name = null)
    {
        $this->twig = $twig;
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        parent::__construct($name);
    }

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $users = $this->userRepository->findAll();

    foreach ($users as $user) {
        $expiresTransactions = $this->transactionRepository->findRecentlyExpiredTransactions($user);

        if ($expiresTransactions) {
            $mailTemplate = $this->twig->render(
                'mail/paymentEndingMailTemplate.html.twig',
                ['transactions' => $expiresTransactions]
        );

            $mail = (new Email())
                ->to($user->getEmail())
                ->from('admin@study-on.local')
                ->subject('Окончание срока аренды')
                ->html($mailTemplate);

            try {
                $this->mailer->send($mail);
            } catch (TransportException $exception) {
                $output->writeln($exception->getMessage());
                $output->writeln('Ошибка при отправке письма пользователю '  . $user->getEmail());

                return Command::FAILURE;
            }
        }
    }


    $output->writeln('Письма успешно отправлены!');
    return Command::SUCCESS;
}
}
