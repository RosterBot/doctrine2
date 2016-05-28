<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\Tests\Models\Quote\Group;
use Doctrine\Tests\Models\Quote\User as QuoteUser;
use Doctrine\Tests\Models\Tweet\Tweet;
use Doctrine\Tests\Models\Tweet\User as TweetUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @author Michaël Gallego <mic.gallego@gmail.com>
 */
class PersistentCollectionCriteriaTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->enableQuotes = true;

        $this->useModelSet('tweet');
        $this->useModelSet('quote');
        
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->_em) {
            $this->_em->getConfiguration()->setEntityNamespaces(array());
        }
        parent::tearDown();
    }

    public function loadTweetFixture()
    {
        $author = new TweetUser();
        $author->name = 'ngal';
        $this->_em->persist($author);

        $tweet1 = new Tweet();
        $tweet1->content = 'Foo';
        $author->addTweet($tweet1);

        $tweet2 = new Tweet();
        $tweet2->content = 'Bar';
        $author->addTweet($tweet2);

        $this->_em->flush();

        unset($author);
        unset($tweet1);
        unset($tweet2);

        $this->_em->clear();
    }

    public function loadQuoteFixture()
    {
        $user = new QuoteUser();
        $user->name = 'mgal';
        $this->_em->persist($user);

        $quote1 = new Group('quote1');
        $user->groups->add($quote1);

        $quote2 = new Group('quote2');
        $user->groups->add($quote2);

        $this->_em->flush();

        $this->_em->clear();
    }

    public function testCanCountWithoutLoadingPersistentCollection()
    {
        $this->loadTweetFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Tweet\User');

        $user   = $repository->findOneBy(array('name' => 'ngal'));
        $tweets = $user->tweets->matching(new Criteria());

        self::assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $tweets);
        self::assertFalse($tweets->isInitialized());
        self::assertCount(2, $tweets);
        self::assertFalse($tweets->isInitialized());

        // Make sure it works with constraints
        $tweets = $user->tweets->matching(new Criteria(
            Criteria::expr()->eq('content', 'Foo')
        ));

        self::assertInstanceOf('Doctrine\ORM\LazyCriteriaCollection', $tweets);
        self::assertFalse($tweets->isInitialized());
        self::assertCount(1, $tweets);
        self::assertFalse($tweets->isInitialized());
    }

    /*public function testCanCountWithoutLoadingManyToManyPersistentCollection()
    {
        $this->loadQuoteFixture();

        $repository = $this->_em->getRepository('Doctrine\Tests\Models\Quote\User');

        $user   = $repository->findOneBy(array('name' => 'mgal'));
        $groups = $user->groups->matching(new Criteria());

        self::assertInstanceOf('Doctrine\ORM\LazyManyToManyCriteriaCollection', $groups);
        self::assertFalse($groups->isInitialized());
        self::assertCount(2, $groups);
        self::assertFalse($groups->isInitialized());

        // Make sure it works with constraints
        $criteria = new Criteria(Criteria::expr()->eq('name', 'quote1'));
        $groups   = $user->groups->matching($criteria);

        self::assertInstanceOf('Doctrine\ORM\LazyManyToManyCriteriaCollection', $groups);
        self::assertFalse($groups->isInitialized());
        self::assertCount(1, $groups);
        self::assertFalse($groups->isInitialized());
    }*/
}
