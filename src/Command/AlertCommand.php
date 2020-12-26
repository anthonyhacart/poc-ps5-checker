<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Notifier\Bridge\Telegram\Reply\Markup\Button\InlineKeyboardButton;
use Symfony\Component\Notifier\Bridge\Telegram\Reply\Markup\InlineKeyboardMarkup;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface as HttpClientTransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class AlertCommand
 * @package App\Command
 */
class AlertCommand extends Command
{
    /**
     *
     */
    public const WEBSTORES = [
        'Auchan' => [
            'store' => 'Auchan',
            'link' => 'https://www.auchan.fr/sony-console-ps5-edition-standard/p-c1315865'
        ],
        'Leclerc' => [
            'store' => 'Leclerc',
            'link' => 'https://www.culture.leclerc/jeux-video-u/playstation-5-u/consoles-u/console-playstation-5---edition-standard-0711719395201-pr?awc=15135_1608992020_4726c51f61dba0f9ad7b3fc2355c996b',
            'unavailable_string' => 'Momentanément indisponible'
        ],
        'Amazon' => [
            'store' => 'Amazon',
            'link' => 'https://www.amazon.fr/gp/product/B08H93ZRK9',
            'unavailable_string' => 'Actuellement indisponible.'
        ],
    ];

    protected static $defaultName = 'app:ps5-alert';

    private HttpClientInterface $httpClient;
    private ChatterInterface $notifier;

    /**
     * AlertCommand constructor.
     * @param HttpClientInterface $httpClient
     * @param ChatterInterface $notifier
     */
    public function __construct(HttpClientInterface $httpClient, ChatterInterface $notifier)
    {
        $this->httpClient = $httpClient;
        $this->notifier = $notifier;
        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setDescription('PS5 alert');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws TransportExceptionInterface
     * @throws HttpClientTransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (self::WEBSTORES as $store => $info) {
            if ($this->checkStatus($info)) {
                $this->sendNotification($info);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param array $info
     * @return bool
     * @throws HttpClientTransportExceptionInterface
     */
    private function checkStatus(array $info): bool
    {
        $response = $this->httpClient->request('GET', $info['link']);

        switch ($info['store']) {
            case 'Leclerc':
            case 'Amazon':
                return !u($response->getContent())->containsAny($info['unavailable_string']);
            default:
                return !(200 !== $response->getStatusCode());
        }
    }

    /**
     * @param array $info
     * @throws TransportExceptionInterface
     */
    private function sendNotification(array $info): void
    {
        $chatMessage = new ChatMessage(sprintf('PS5 DISPO CHEZ %s', $info['store']));

        $telegramOptions = (new TelegramOptions())
            ->parseMode('MarkdownV2')
            ->replyMarkup((new InlineKeyboardMarkup())
                ->inlineKeyboard([
                    (new InlineKeyboardButton(sprintf('Aller sur %s', $info['store'])))
                        ->url($info['link'])
                        ->payButton(true),
                ])
            );

        $chatMessage->options($telegramOptions);

        $this->notifier->send($chatMessage);
    }
}