<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\Assert\Assert;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('docusign');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('docusign');
        }

        $rootNode
            ->children()
                ->booleanNode('demo')
                    ->info('Enable the demo mode')
                    ->defaultFalse()
                ->end()
                ->scalarNode('account_id')
                    ->info('Obtain your accountId from DocuSign: the account id is shown in the drop down on the upper right corner of the screen by your picture or the default picture')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_signer_name')
                    ->info('Recipient Information as the signer full name')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_signer_email')
                    ->info('Recipient Information as the signer email')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static function ($v) {
                            try {
                                Assert::email($v);

                                return false;
                            } catch (\Exception $e) {
                                return true;
                            }
                        })
                        ->thenInvalid('Invalid email %s')
                    ->end()
                ->end()
                ->scalarNode('api_uri')
                    ->info('DocuSign production API URI (default: https://www.docusign.net/restapi)')
                    ->cannotBeEmpty()
                    ->defaultValue('https://www.docusign.net/restapi')
                ->end()
                ->scalarNode('callback_route_name')
                    ->info('Where does DocuSign redirect the user after the document has been signed. Use a route name')
                    ->cannotBeEmpty()
                    ->defaultValue('docusign_callback')
                ->end()
                ->scalarNode('webhook_route_name')
                    ->info('Where does DocuSign send the event notifications during the signature. Use a route name')
                    ->cannotBeEmpty()
                    ->defaultValue('docusign_webhook')
                ->end()
                ->booleanNode('signatures_overridable')
                    ->info('Let the user override the signature position through the request')
                    ->defaultFalse()
                ->end()
                ->arrayNode('signatures')
                    ->info('Position the signatures on a page, X and Y axis of your documents')
                    ->useAttributeAsKey('document_name')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->arrayNode('signatures')
                                ->info('Document signatures')
                                ->arrayPrototype()
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('page')->isRequired()->info('Page number where to apply the signature (default: 1)')->defaultValue(1)->end()
                                        ->scalarNode('x_position')->isRequired()->info('X position of the signature (top left corner)')->end()
                                        ->scalarNode('y_position')->isRequired()->info('Y position of the signature (top left corner)')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('jwt')
                    ->isRequired()
                    ->info('Configure JSON Web Token (JWT) authentication: https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken')
                    ->children()
                        ->scalarNode('private_key')->isRequired()->info('Path to the private RSA key generated by DocuSign')->end()
                        ->scalarNode('integration_key')->isRequired()->info('To generate your integration key, follow this documentation: https://developers.docusign.com/esign-soap-api/reference/Introduction-Changes/Integration-Keys')->end()
                        ->scalarNode('user_guid')->isRequired()->info('Obtain your user UID (also called API username) from DocuSign Admin > Users > User > Actions > Edit')->end()
                    ->end()
                ->end()
            ->end();

        $this->addStorageCompat($rootNode);

        return $treeBuilder;
    }

    /*
     * Add compatibility for flysystem in symfony 3.4
     */
    private function addStorageCompat(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->end()
                            ->arrayNode('options')
                                ->variablePrototype()
                            ->end()
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('visibility')->defaultNull()->end()
                        ->booleanNode('case_sensitive')->defaultTrue()->end()
                        ->booleanNode('disable_asserts')->defaultFalse()->end()
                    ->end()
                ->end()
                ->defaultValue([])
            ->end();
    }
}
