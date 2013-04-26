<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace library\Soap;

use library\Soap\NfephpSoapClient;
use library\Exception\NfephpException;

class NatSoap{

    /**
     * send
     * Estabelece comunicaçao com servidor SOAP 1.1 ou 1.2 da SEFAZ,
     * usando as chaves publica e privada parametrizadas na contrução da classe.
     * Conforme Manual de Integração Versão 4.0.1 
     *
     * @name send
     * @param string $urlsefaz
     * @param string $namespace
     * @param string $cabecalho
     * @param string $dados
     * @param string $metodo
     * @param numeric $ambiente  tipo de ambiente 1 - produção e 2 - homologação
     * @param string $UF unidade da federação, necessário para diferenciar AM, MT e PR
     * @return mixed false se houve falha ou o retorno em xml do SEFAZ
     */
    public function send($urlsefaz = '', $namespace = '', $cabecalho = '', $dados = '', $metodo = '', $ambiente = '2', $UF = '')
    {
        try {
            if (!class_exists("SoapClient")) {
                $msg = "A classe SOAP não está disponível no PHP, veja a configuração.";
                throw new NfephpException($msg);
            }
            //ativa retorno de erros soap
            use_soap_error_handler(true);
            //versão do SOAP
            $soapver = SOAP_1_2;
            if ($ambiente == 1) {
                $ambiente = 'producao';
            } else {
                $ambiente = 'homologacao';
            }
            //monta a terminação do URL
            switch ($metodo){
                case 'nfeRecepcaoLote2':
                    $usef = "_NFeRecepcao2.asmx";
                    break;
                case 'nfeRetRecepcao2':
                    $usef = "_NFeRetRecepcao2.asmx";
                    break;
                case 'nfeCancelamentoNF2':
                    $usef = "_NFeCancelamento2.asmx";
                    break;
                case 'nfeInutilizacaoNF2':
                    $usef = "_NFeInutilizacao2.asmx";
                    break;
                case 'nfeConsultaNF2':
                    $usef = "_NFeConsulta2.asmx";
                    break;
                case 'nfeStatusServicoNF2':
                    $usef = "_NFeStatusServico2.asmx";
                    break;
                case 'consultaCadastro':
                    $usef = "";
                    break;
            }
            //para os estados de AM, MT e PR é necessário usar wsdl baixado para acesso ao webservice
            if ($UF=='AM' || $UF=='MT' || $UF=='PR') {
                $urlsefaz = "$this->URLbase/wsdl/2.00/$ambiente/$UF$usef";
            }
            if ($this->enableSVAN) {
                //se for SVAN montar o URL baseado no metodo e ambiente
                $urlsefaz = "$this->URLbase/wsdl/2.00/$ambiente/SVAN$usef";
            }
            //verificar se SCAN ou SVAN
            if ($this->enableSCAN) {
                //se for SCAN montar o URL baseado no metodo e ambiente
                $urlsefaz = "$this->URLbase/wsdl/2.00/$ambiente/SCAN$usef";
            }
            if ($this->soapTimeout == 0) {
                $tout = 999999;
            } else {
                $tout = $this->soapTimeout;
            }
            //completa a url do serviço para baixar o arquivo WSDL
            $URL = $urlsefaz.'?WSDL';
            $this->soapDebug = $urlsefaz;
            $options = array(
                'encoding'      => 'UTF-8',
                'verifypeer'    => false,
                'verifyhost'    => true,
                'soap_version'  => $soapver,
                'style'         => SOAP_DOCUMENT,
                'use'           => SOAP_LITERAL,
                'local_cert'    => $this->certKEY,
                'trace'         => true,
                'compression'   => 0,
                'exceptions'    => true,
                'connection_timeout' => $tout,
                'cache_wsdl'    => WSDL_CACHE_NONE
            );
            //instancia a classe soap
            $oSoapClient = new NfeSoapClient($URL, $options);
            //monta o cabeçalho da mensagem
            $varCabec = new SoapVar($cabecalho, XSD_ANYXML);
            $header = new SoapHeader($namespace, 'nfeCabecMsg', $varCabec);
            //instancia o cabeçalho
            $oSoapClient->setSoapHeaders($header);
            //monta o corpo da mensagem soap
            $varBody = new SoapVar($dados, XSD_ANYXML);
            //faz a chamada ao metodo do webservices
            $resp = $oSoapClient->soapCall($metodo, array($varBody));
            if (is_soap_fault($resp)) {
                $soapFault = "SOAP Fault: (faultcode: {$resp->faultcode}, faultstring: {$resp->faultstring})";
            }
            $resposta = $oSoapClient->getLastResponse();
            $this->soapDebug .= "\n" . $soapFault;
            $this->soapDebug .= "\n" . $oSoapClient->getLastRequestHeaders();
            $this->soapDebug .= "\n" . $oSoapClient->getLastRequest();
            $this->soapDebug .= "\n" . $oSoapClient->getLastResponseHeaders();
            $this->soapDebug .= "\n" . $oSoapClient->getLastResponse();
        } catch (NfephpException $e) {
            $this->setError($e->getMessage());
            throw $e;
            return false;
        }
        return $resposta;
    } //fim nfeSOAP
    
}//fim da classe NatSoap
