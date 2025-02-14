<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\EnvelopeSummary;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\Events\PreSendEnvelopeEvent;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class SendEnvelopeTest extends TestCase
{
    use ProphecyTrait;

    private $envelopeBuilderProphecyMock;
    private $grantProphecyMock;
    private $routerProphecyMock;
    private $envelopesApiProphecyMock;
    private $envelopeSummaryProphecyMock;
    private $eventDispatcherProphecyMock;

    protected function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->grantProphecyMock = $this->prophesize(GrantInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->envelopesApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $this->envelopeSummaryProphecyMock = $this->prophesize(EnvelopeSummary::class);
        $this->eventDispatcherProphecyMock = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testItCreatesASendEnvelope(): void
    {
        $envelopeDefinitionProphecyMock = $this->prophesize(EnvelopeDefinition::class);
        $envelopeDefinitionProphecyMock->__toString()->willReturn('definition');
        $envelopeDefinition = $envelopeDefinitionProphecyMock->reveal();

        $this->envelopeSummaryProphecyMock->getEnvelopeId()->shouldBeCalled()->willReturn('envelopeId');
        $this->envelopesApiProphecyMock->createEnvelope(1234567, $envelopeDefinition)->shouldBeCalled()->willReturn($this->envelopeSummaryProphecyMock->reveal());

        $this->envelopeBuilderProphecyMock->getEnvelopeDefinition()->shouldBeCalled()->willReturn($envelopeDefinition);
        $this->envelopeBuilderProphecyMock->getAccountId()->shouldBeCalled()->willReturn(1234567);
        $this->envelopeBuilderProphecyMock->getApiUri()->shouldBeCalled()->willReturn('uri');
        $this->envelopeBuilderProphecyMock->getEnvelopesApi()->shouldBeCalled()->willReturn($this->envelopesApiProphecyMock->reveal());
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');

        $this->grantProphecyMock->__invoke()->shouldBeCalled()->willReturn('grant');
        $this->envelopeBuilderProphecyMock->setEnvelopesApi(Argument::type(EnvelopesApi::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setEnvelopeId('envelopeId')->shouldBeCalled();

        $this->eventDispatcherProphecyMock->dispatch(Argument::Type(PreSendEnvelopeEvent::class))->shouldBeCalled();

        $sendEnvelope = new SendEnvelope($this->envelopeBuilderProphecyMock->reveal(), $this->grantProphecyMock->reveal(), $this->routerProphecyMock->reveal(), $this->eventDispatcherProphecyMock->reveal());
        $sendEnvelope(['signature_name' => 'default']);
    }
}
