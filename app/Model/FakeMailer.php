<?php

namespace App\Model;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

/**
 * No-op mailer for environments with no real mail transport (CI, local dev
 * without a configured MTA) - see app/config/config.mock-mailer.neon and the
 * MOCK_MAILER env var in app/bootstrap.php. Swapping this in must not change
 * any caller's control flow, only skip the actual network/sendmail call.
 */
class FakeMailer implements Mailer
{
	public function send(Message $mail): void
	{
	}
}
