<?php declare(strict_types = 1);

require_once '../vendor/autoload.php';

// Would use filter_input on INPUT_ENV, but $_ENV can be empty...
// User name regex taken from https://github.com/shinnn/github-username-regex
$username = filter_var(getenv("GITHUB_USERNAME"), FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '@^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$@i']]);
$token = filter_var(getenv("GITHUB_SECRET_TOKEN"), FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '@^[a-f0-9]{40}$@']]);

// If the environment does not include the GitHub information, error out.
if (false === $username || false === $token) {
    header('HTTP/1.1 500 Internal Server Error');
    exit(0);
}

// Request the last 100 notifications for the user.
$client = \Symfony\Component\HttpClient\HttpClient::create();
$response = $client->request('GET', 'https://api.github.com/notifications', [
    'auth_basic' => [$username, $token],
    'headers' => ['Accept' => 'application/vnd.github.v3+json'],
    'query' => [
        'per_page' => 100,
        'all' => true,
    ]
]);

// Parse the response, or die trying.
try {
    $notifications = json_decode($response->getContent());
} catch (\Throwable $thrown) {
    header('HTTP/1.1 500 Internal Server Error');
    var_dump($thrown);
    exit(0);
}

// Rewrite REST API object links to functioning public-web links.
function rewriteLink(string $url, ?string $comment = null): string {
    $url = str_replace('https://api.github.com/repos/', 'https://github.com/', $url);
    $url = preg_replace('@(github\.com\/[^/]+\/[^/]+\/)pulls\/@', '$1issues/', $url, 1);
    if (null !== $comment && 1 === preg_match('@\/(\d+)$@', $comment, $commentId)) {
        $url .= '#issuecomment-' . $commentId[1];
    }
    return $url;
}

// Never write a serialisation format (like XML or JSON) by hand.
$feed = new \FeedIo\Feed();
$feed->setTitle('Notifications for ' . $username);
$feed->setLastModified(new DateTime($notifications[0]->updated_at));
foreach ($notifications as $notification) {
    $item = new \FeedIo\Feed\Item();
    $item->setTitle('[' . $notification->subject->type . '] ' . $notification->subject->title);
    $item->setLink(rewriteLink($notification->subject->url, $notification->subject->latest_comment_url));
    $author = new \FeedIo\Feed\Item\Author();
    $author->setName($notification->repository->full_name);
    $author->setUri($notification->repository->html_url);
    $item->setAuthor($author);
    $item->setLastModified(new DateTime($notification->updated_at));
    $feed->add($item);
}

header('Content-Type: application/atom+xml');
print \FeedIo\Factory::create()->getFeedIo()->toAtom($feed);
