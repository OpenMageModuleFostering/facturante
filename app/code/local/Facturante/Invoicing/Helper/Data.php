<?php
/**
 * Common module functions
 *
 * @category  Facturante
 * @package   Facturante_Invoicing
 */
class Facturante_Invoicing_Helper_Data extends Mage_Core_Helper_Abstract
{

    const FACTURANTE_API_CALL_STATUS_OK = 'OK';
    const FACTURANTE_CODIGO_FACTURA = 'factura';
    const FACTURANTE_CODIGO_NOTA_DE_DEBITO = 'debito';
    const FACTURANTE_CODIGO_NOTA_DE_CREDITO = 'credito';

    public function testAPIConnection() {
        $storeId = 0;
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
        {
            $storeId = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
        {
            $websiteId = Mage::getModel('core/website')->load($code)->getId();
            $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        }

//        Mage::log("Store ID: -> " . $storeId, null,'debug1.log');

        /** Si aún no se ha configurado la url, entonces seteo la de testing. De ese modo permite ingresar al system config y cambiarla. */
        $urlApi = Mage::getStoreConfig('facturante/connection/api_url', $storeId);
        if( empty( $urlApi ) ) {
            $urlApi = "http://testing.facturante.com/api/comprobantes.svc?wsdl";
        }
        $client = new SoapClient( $urlApi );

        $usernameValue = Mage::getStoreConfig('facturante/connection/username', $storeId);
        $passwordValue = Mage::getStoreConfig('facturante/connection/password', $storeId);
        $businessIdValue = Mage::getStoreConfig('facturante/connection/business_id', $storeId);

        $auth = array(
            'Empresa' => $businessIdValue,
            'Hash' => $passwordValue,
            'Usuario' => $usernameValue
        );

        $param = array(
            'Autenticacion' => $auth,
            'FechaDesde' => self::getCurrentFormattedDate(),
            'FechaHasta' => self::getCurrentFormattedDate(),
            'NroPagina' => 1,
        );

        $request = array('request' => $param);
        $response = $client->ListadoComprobantes($request);
        $estado = $response->ListadoComprobantesResult->Estado;

        if ($estado == self::FACTURANTE_API_CALL_STATUS_OK)
        {
            return true;
        }

        return false;
    }

    /**
     * Generar factura/nota de débito/nota de crédito a traves de la API de facturante
     * Posibles valores para $type: 'factura', 'debito' o 'credito'
     *
     * @param int $order
     * @param string $type
     */
    public function generateInvoiceForOrder($order, $type)
    {
        $invoiceStatus = $order->getFacturanteInvoiceStatus();
        $lastInvoiceType = $order->getAfipLastInvoiceType();

        // Si no hay invoice type, quiere decir que nunca se realizo un intento de generación de factura
        // por lo tanto el unico comprobante que puede hacerse es factura
        if (!$lastInvoiceType && $type != self::FACTURANTE_CODIGO_FACTURA)
        {
            Mage::getSingleton('core/session')->addNotice('Ninguna factura ha sido realizada en esta orden, para realizar una nota de débito o crédito primero la orden debe ser facturada.');
            return false;
        }
        // Si el invoice status es 'Esperando CAE', entonces no permitir
        // generar una nuevo comprobante ya que se está generando
        if ($invoiceStatus == 'Esperando CAE' || $invoiceStatus == 'Enviando')
        {
            Mage::getSingleton('core/session')->addNotice('Existe un comprobante generandose para la orden Nª ' . $order->getIncrementId() . '. Por favor, espere unos momentos para que el comprobante sea procesado.');
            return false;
        }

        // Si el ultimo comprobante que tiene un pedido es una factura
        // (en cualquiera de sus estados, procesado , leido),
        // lo único que puede hacerse es una nota de credito
        if ($lastInvoiceType == self::FACTURANTE_CODIGO_FACTURA
            && $type != self::FACTURANTE_CODIGO_NOTA_DE_CREDITO
            && $invoiceStatus != 'Error en Comprobante')
        {
            Mage::getSingleton('core/session')->addNotice('Ya se ha generado una factura para la orden Nª ' . $order->getIncrementId() . '. Solo es posible generar una nota de crédito para esta orden.');
            return false;
        }

        // Si el ultimo comprobante que tiene un pedido es una nota de crédito,
        // solo puede hacerse una factura o nota de debito
        if ($lastInvoiceType == self::FACTURANTE_CODIGO_NOTA_DE_CREDITO
            && $type == self::FACTURANTE_CODIGO_NOTA_DE_CREDITO)
        {
            Mage::getSingleton('core/session')->addNotice('Ya se ha generado una nota de crédito para la orden Nª ' . $order->getIncrementId() . '. Solo es posible generar una nueva factura o nota de débito para esta orden.');
            return false;
        }

        // Si el ultimo comprobante que tiene un pedido es una nota de debito,
        // no debe permitir hacer ningun comprobante mas.
        if ($lastInvoiceType == self::FACTURANTE_CODIGO_NOTA_DE_DEBITO)
        {
            Mage::getSingleton('core/session')->addNotice('Ya se ha generado una nota de débito para la orden Nª ' . $order->getIncrementId() . '. No es posible realizar una nueva factura o nota de crédito para esta orden.');
            return false;
        }
        $billingAddress = $order->getBillingAddress();

        $storeId = $order->getStoreId();
//        Mage::log("Store ID: -> " . $storeId, null,'debug1.log');

        $urlApi = Mage::getStoreConfig('facturante/connection/api_url', $storeId);
        if( empty( $urlApi ) ) {
            $urlApi = "http://testing.facturante.com/api/comprobantes.svc?wsdl";
        }
        $client = new SoapClient( $urlApi );

        $usernameValue = Mage::getStoreConfig('facturante/connection/username', $storeId);
        $passwordValue = Mage::getStoreConfig('facturante/connection/password', $storeId);
        $businessIdValue = Mage::getStoreConfig('facturante/connection/business_id', $storeId);

        $auth = array(
            'Empresa' => $businessIdValue,
            'Hash' => $passwordValue,
            'Usuario' => $usernameValue
        );

        /**
         * Razon Social
         *
         * Nombre de la persona física o jurídica del cliente a
         * emitir el comprobante(receptor del comprobante)
         */
        $razonSocial = $billingAddress->getName();
        if ($company = $billingAddress->getCompany())
        {
            $razonSocial = $company;
        }

        /**
         * Numero de DNI
         */
        $customerId = $order->getCustomerId();
        $customer = Mage::getModel('customer/customer')->load($customerId);

        /**
         *  @var  $configTaxVatComoDni
         *  0: Usa campo DNI
         *  1: Usa Tax/Vat como número de DNI
         */
        $configTaxVatComoDni = Mage::getStoreConfig('facturante/connection2/campo_tax_vat_dni', $storeId);
        if( isset ( $configTaxVatComoDni ) && $configTaxVatComoDni == 1) {
            $numeroDeDocumento = preg_replace('/[^0-9]/','', $customer->getData('taxvat'));
        } else {
            $numeroDeDocumento = preg_replace('/[^0-9]/','', $customer->getData('dni_number'));
        }

        // Si no hay numero de documento para el customer,
        // sino asignar customer ID como numero de documento
        // si no hay customer ID, entonces es guest order, pasar numero fijo 1
        if (!$numeroDeDocumento)
        {
            if ($customerId)
            {
                $numeroDeDocumento = $customerId;
            } else
            {
                $numeroDeDocumento = 1;
            }
        }

        // Verificar si hay numero de documento en el billing address,
        // si existe entonces ese va a a ser el numero de documento
        $dniNumberAddress = preg_replace('/[^0-9]/','', $order->getBillingAddress()->getData('dni_number_address'));

        if ($dniNumberAddress)
        {
            $numeroDeDocumento = $dniNumberAddress;
        }

        /**
         * Percibe IVA?
         *
         * True: Si la empresa emisora es Agente de Retención de IVA
         *  0: NO
         *  1: SI
         * @type boolean
         */
        $percibeIva = Mage::getStoreConfig('facturante/connection3/percibe_iva', $storeId);

        /**
         * Percibe IIBB?
         *
         * True: Si la empresa emisora es Agente de Retención de ARBA
         *
         * @type boolean
         */
//        $percibeIIBB = false;
        $percibeIIBB = 0;

        /**
         * Tratamiento Impositivo
         *
         * 1: MONOTRIBUTISTA
         * 2: RESPONSABLE INSCRIPTO
         * 3: CONSUMIDOR FINAL
         * 4: IVA EXENTO
         * 5: IVA NO RESPONSABLE
         *
         * @type int
         */
        $tratamientoImpositivo = $customer->getTaxType();
        if (!$tratamientoImpositivo)
        {
            $tratamientoImpositivo = 3;
        }

        // Si el numero ingresado en el documento es un numero de CUIT,
        // entonces el tratamiento impositivo debe ser responsable inscripto
        if ( self::isCUIT( $numeroDeDocumento )  ) {
            $tratamientoImpositivo = 2;
        }
        /**
         * Enviar comprobante?
         *
         * Si se envía el comprobante por email al cliente
         *
         * @type bool
         */
        $enviarComprobante = Mage::getStoreConfig('facturante/connection2/enviar_comprobante', $storeId);

        /**
         * Provincia
         */
        $regionId = $order->getBillingAddress()->getData('region_id');
        $region = Mage::getModel('directory/region')->load($regionId);
        $provincia = $region->getName();

        /**
         * Condición de Pago
         *
         * Soporte por defecto a métodos nativos
         * de Magento que además sean válidos para la AFIP
         *
         * 1= Contado
         * 2= Cuenta Corriente
         * 3= Tarjeta de Debito
         * 4= Tarjeta de Credito
         * 5= Cheque
         * 6= Ticket
         * 7= Otro
         * 8= MercadoPago
         * 9= Cobro Digital
         * 10= DineroMail
         * 11= Decidir
         * 12= TodoPago
         *
         * @type int
         */
        $paymentMethod = 1;
        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if ($payment == 'ccsave' ||
            $payment == 'moneybookers_acc' ||
            $payment == 'authorizenet_directpost' ||
            $payment == 'payflow_advanced' ||
            $payment == 'payflow_link' ||
            $payment == 'authorizenet')
        {
            $paymentMethod = 4;
        } else if ($payment == 'checkmo')
        {
            $paymentMethod = 5;
        } else if ($payment == 'banktransfer')
        {
            $paymentMethod = 2;
        }

        /**
         * Fecha del comprobante
         *
         * Fecha y hora actual en formato: "2016-01-18T00:00:00"
         *
         * @type datetime
         */
        $fechaHora = self::getCurrentFormattedDate();

        $cliente = array(
            "RazonSocial" => $razonSocial,
            "NroDocumento" => $numeroDeDocumento,
            "DireccionFiscal" => $order->getBillingAddress()->getData('street'),
            "Provincia" => $provincia,
            "CodigoPostal" => $billingAddress->getPostcode(),
            "PercibeIVA" => $percibeIva,
            "PercibeIIBB" => $percibeIIBB,
            "TratamientoImpositivo" => $tratamientoImpositivo,
            "CondicionPago" => $paymentMethod,
            "EnviarComprobante" => $enviarComprobante,
            "MailFacturacion" => $billingAddress->getEmail(),
            "MailContacto" => $order->getCustomerEmail(),
            "Contacto" => $order->getCustomerName(),
            "Telefono" => $billingAddress->getTelephone()
        );

        /**
         * Prefijo
         *
         * Siempre completar con ceros adelante del número si es menor a 4 digitos. Ej: 0004
         *
         * @type int
         */
        $prefijo = Mage::getStoreConfig('facturante/connection3/prefijo', $storeId);
        $longitudActual = strlen($prefijo);
        $longitudPrefijo = 4;

        $diferenciaLongitudes = $longitudPrefijo - $longitudActual;

        if($diferenciaLongitudes >= 1)
        {
            for($i = 0; $i < $diferenciaLongitudes; $i++)
            {
                $prefijo = '0' . $prefijo;
            }
        }

        /**
         * Tipo de comprobante
         *
         * FA: FACTURA A
         * NCA: NOTA DE CREDITO A
         * NDA: NOTA DE DEBITO A
         *
         * @type string
         */
        $tipoComprobante = 'F';
        if ($type == self::FACTURANTE_CODIGO_NOTA_DE_CREDITO)
        {
            $tipoComprobante = 'NC';
        } else if ($type == self::FACTURANTE_CODIGO_NOTA_DE_DEBITO)
        {
            $tipoComprobante = 'ND';
        }

        /**
         * Percepcion IVA
         *
         * Importe Total correspondiente al % aplicado en concepto de percepción de
         * ARBA al cliente - SOLO Si la empresa emisora es Agente de Retención de ARBA. Por defecto = 0
         *
         * @type int
         */
        $percepcionIVA = 0;

        /**
         * Percepcion IIBB - numeric(18,3)
         *
         * Total correspondiente al % aplicado en concepto de percepción de IVA al cliente
         * SOLO Si la empresa emisora es Agente de Retención de IVA. Por defecto = 0
         *
         * @type int
         */
        $percepcionIIBB = 0;

        /**
         * Bienes
         *
         * 1- Bienes
         * 2- Servicios
         * 3- Productos y Servicios
         *
         * @type int
         */
        $bienes = Mage::getStoreConfig('facturante/connection3/bienes', $storeId);
        /**
         * Fecha de servicio facturado (desde y hasta)
         *
         * @type datetime
         */
        $fechaServDesde = self::getCurrentFormattedDate();
        $fechaServHasta = self::getCurrentFormattedDate();

        /**
         * Fecha de servicio facturado (desde y hasta)
         *
         * @type datetime
         */
        $fechaVencPago = self::getCurrentFormattedDate();

        /**
         * Importe correspondiente a impuestos internos aplicados
         *
         * @type int
         */
        $importeImpInternos = 0;

        /**
         * Importe correspondiente a impuestos municipales aplicados
         *
         * @type int
         */
        $importeImpMunicipales = 0;

        /**
         * Moneda
         *
         * Código numérico de Moneda correspondiente. Actualmente AFIP solo admite PESOS = 2,
         * por lo que todos los importes del comprobante son expresados en pesos Arg.
         *
         * @type int
         */
        $moneda = 2;

        /**
         * Tipo de cambio
         *
         * Factor de conversión a la moneda seleccionada. Para PESOS Arg = 1
         *
         * @type int
         */
        $tipoCambio = 1;

        /**
         * Subtotal no alcanzado
         *
         * Sumatoria de los totales NO alcanzados(productos específicos determinados por AFIP)
         *
         * @type int
         */
        $subtotalNoAlcanzado = 0;

        /**
         * Subtotal exento
         *
         * Sumatoria de los totales exentos (rubros o articulos específicos determinados por AFIP. Ej: entradas de espectáculos)
         *
         * @type int
         */
        $subtotalExento = 0;

        /**
         * Porcentaje IIBB
         *
         * Utilizada si la empresa emisora es agente de retención de ARBA
         * Alícuota definida por ARBA aplicada en la percepción de IIBB. Default = 0
         *
         * @type int
         */
        $porcentajeIIBB = 0;

        // Valores a omitir (omitir, no cero):
        // subTotal
        // subTotalExento
        // subTotalNoAlcanzado
        // total
        // totalNeto

        /**
         *  ----- Funcionalidad GTIN -----
         *
         * @var  $config_modo_facturacion
         *
         * '' (vacio): No se ha seteado el dato en el sistem config
         * 1: Factura Electrónica Genérica  -- AFIP RG 2485 o RG 3749
         * 2: Factura Electrónica con Detalle -- (Mtx o Matrix) - RG 2904
         *
         */
        $config_modo_facturacion = Mage::getStoreConfig('facturante/connection5/modo_facturacion', $storeId);

        $encabezado = array(
            "FechaHora" => $fechaHora,
            "Prefijo" => $prefijo,
            "TipoComprobante" => $tipoComprobante,

            "PercepcionIVA" => $percepcionIVA,
            "PercepcionIIBB" => $percepcionIIBB,
            "OrdenCompra" => $order->getIncrementId(),

            "Bienes" => $bienes,
            "EnviarComprobante" => $enviarComprobante,
            "FechaServDesde" => $fechaServDesde,
            "FechaServHasta" => $fechaServHasta,
            "FechaVtoPago" => $fechaVencPago,

            "ImporteImpuestosInternos" => $importeImpInternos,
            "ImportePercepcionesMunic" => $importeImpMunicipales,

            "Moneda" => $moneda,

            "TipoDeCambio" => $tipoCambio,
            "CondicionVenta" => $paymentMethod,
            "PorcentajeIIBB" => $porcentajeIIBB
        );

        $orderedItems = $order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('product_type', array('eq'=>'simple'))
            ->load();

        $preciosFinales = Mage::getStoreConfig('facturante/connection2/precios_finales', $storeId);

        $items = array();
        $productosConGtinFaltantes = 0;
        foreach ($orderedItems as $item) {
            if( $item->getProductType() == 'simple' ) {
                /** Se evalúa la toma de datos según el tipo de producto. */
                if ( $item->getParentItem() ) {
                    $price = $item->getParentItem()->getPrice();
                    $rowDiscount = $item->getParentItem()->getDiscountAmount();
                } else {
                    $price = $item->getPrice();
                    $rowDiscount = $item->getDiscountAmount();
                }

//                Mage::log("Price: -> ".$price, null,'debug1.log');

                $qtyOrdered = $item->getData('qty_ordered');

                /** @var  $itemDiscount
                 *  Descuento unitario del producto.
                 * */
                $itemDiscount = $rowDiscount / $qtyOrdered;

//                Mage::log("item discount amount: -> ".$itemDiscount, null,'debug1.log');

                /** @var  $finalPrice
                 *  Precio del producto menos el descuento aplicado de forma unitaria.
                 */
//                $finalPrice = $price - $itemDiscount;
                $finalPrice = $price;

//                Mage::log("Discount amount: -> ".$rowDiscount, null,'debug1.log');

//                Mage::log("Final Price (after discount): -> ".$finalPrice."\n", null,'debug1.log');

                /** NO ES PRECIO FINAL */
                if ( $preciosFinales == 0 ) {
                    // En este caso, el precio final de los productos no incluye los impuestos
                    // El impuesto total para el producto va a ser el monto de impuesto dividido la cantidad
                    // de productos comprados
                    /** Tax seteado en Magento
                     *  El taxClass cuyo codigo es "0" es taxClass vacio.
                     */
                    $productTaxClass = $item->getProduct()->getTaxClassId();

                    /** Si taxClass es 'none', dejo un 21% por default. */
                    if ( $productTaxClass == 0 ) {
                        $porcentageImpuestoProducto = 21;
                    } else {
                        /** Caso contrario, tomo el valor de la clase seleccionada. */
                        $porcentageImpuestoProducto = $item->getTaxPercent();
                    }
//                    $subTotal = $finalPrice * $qtyOrdered;
//                    $total = round( ($subTotal) + $subTotal / 100 * $porcentageImpuestoProducto, 2);

                    $total = round( ( $finalPrice * $qtyOrdered ), 2 );
                } else {
                    /** SI ES PRECIO FINAL */
                    // Si el precio de los productos es final
                    // entonces descontar un 21% del precio del producto
                    // (multiplicar precio de los productos por 1.21)
                    $porcentageImpuestoProducto = 21;
//                    $precioProducto = $finalPrice / 1.21;
                    $finalPrice = $finalPrice / 1.21;
                    $total = round( ( $finalPrice * $qtyOrdered ), 2 );
                }

                $newOrderedItem  = array(
                    "Cantidad" => $qtyOrdered,
                    "Detalle" => $item->getProduct()->getData('description'),
                    "Codigo" => $item->getSku(),
                    "IVA" => $porcentageImpuestoProducto,
                    "PrecioUnitario" => round($finalPrice, 2),
                    "Total" => $total,
                    "Gravado" => true,
                    "Bonificacion" => 0
                );

                /** Si es factura con detalle, entonces agrego dos atributos más. */
                if( $config_modo_facturacion == 2 ) {
                    $gtin = $item->getProduct()->getData('gtin');

                    /** Me fijo que gtin tenga dato. */
                    if( empty( $gtin ) ) {
                        $productosConGtinFaltantes++;
                    }
                    $newOrderedItem['CodigoMtx'] = $gtin;
                    $newOrderedItem['UnidadesMtx'] = $qtyOrdered;
                }
                $items[] = $newOrderedItem;
            }
        }

        // Response
        $responseCrearComprobante = null;
        //Agregar shipping cost al total de la orden como un item

        // Agregar descuentos como valores negativos en el total de la orden como un item (si existen descuentos)
        $discountAmount = abs($order->getDiscountAmount());

        if ( $preciosFinales == 1 ) {
            /** SI ES PRECIO FINAL */

            /** Shipping */
            $precioUnitarioShipping = $order->getShippingAmount() / 1.21;
            $totalShipping = $order->getShippingAmount();
            /** Discount */
            $precioUnitarioDiscount = ( -1 * $discountAmount ) / 1.21;
            $totalDiscount = ( -1 * $discountAmount );

        } else {
            /** NO ES PRECIO FINAL ******  */

            /** Shipping */
            $precioUnitarioShipping = $order->getShippingAmount() / 1.21;
            $totalShipping = $order->getShippingAmount();
            /** Discount */
            $precioUnitarioDiscount = ( -1 * $discountAmount );
            $totalDiscount = ( -1 * $discountAmount ) * 1.21;
        }

        $shippingCost  = array(
            "Cantidad" => 1,
            "Detalle" => 'Gastos de Envío',
            "Codigo" => 'shipping',
            "IVA" => 21,
            "PrecioUnitario" => round( $precioUnitarioShipping, 2),
            "Total" => round( $totalShipping, 2),
            "Gravado" => true,
            "Bonificacion" => 0
        );
        if( $config_modo_facturacion == 2 ) {
            $shippingCost['CodigoMtx'] = 7790001001085;
            $shippingCost['UnidadesMtx'] = 1;
        }
        $items[] = $shippingCost;

        if ($discountAmount > 0) {
            $discounts  = array(
                "Cantidad" => 1,
                "Detalle" => 'Descuentos',
                "Codigo" => 'discounts',
                "IVA" => 21,
                "PrecioUnitario" => round( $precioUnitarioDiscount, 2),
                "Total" => round( $totalDiscount, 2),
                "Gravado" => true,
                "Bonificacion" => 0
            );
            if( $config_modo_facturacion == 2 ) {
                $discounts['CodigoMtx'] = 7790001001030;
                $discounts['UnidadesMtx'] = 1;
            }
            $items[] = $discounts;
        }

        // Parametros
        $paramCrearComprobante = array(
            "Autenticacion" => $auth,
            "Cliente" => $cliente,
            "Encabezado" => $encabezado,
            "Items" => $items
        );

        // Request
        $requestCrearComprobante = array("request" => $paramCrearComprobante);

        /** Factura electrónica genérica */
        if ( $config_modo_facturacion == 1 ) {
            Mage::log($requestCrearComprobante, null,'debug1.log');
            $responseCrearComprobante = $client->CrearComprobanteSinImpuestos( $requestCrearComprobante );
            Mage::log($responseCrearComprobante, null,'debug1.log');
            /**
             * Luego de la llamada para crear el comprobante, solicito los detalles del comprobante para saber en que estado se encuentra.
             */
            $comprobanteId = $responseCrearComprobante->CrearComprobanteSinImpuestosResult->IdComprobante;

            if (!empty($comprobanteId)) {
                $newInvoicingStatus = Mage::getModel('facturante_invoicing/order_invoicingstatus');
                $newInvoicingStatus->setOrderId($order->getId());
                $newInvoicingStatus->setOrderIncrementId($order->getIncrementId());
                $newInvoicingStatus->setIdcomprobante($comprobanteId);
                $detalleComprobante = $this->getDetalleComprobante($auth, $comprobanteId, $client);
                $estadoDetalleComprobante = $detalleComprobante->DetalleComprobanteResult->Comprobante->EstadoComprobante;

                $estadoDetalleComprobanteString = $this->getStringEstadoComprobante($estadoDetalleComprobante);
                $newInvoicingStatus->setStatus($estadoDetalleComprobanteString);
                if($estadoDetalleComprobante == 4) {
                    $newInvoicingStatus->setLink($detalleComprobante->DetalleComprobanteResult->Comprobante->URLPDF);
                }
                /** Aqui se guarda el mensaje de respuesta de la API */
                $detalleApiError = $this->getDetalleApiRespuesta($responseCrearComprobante->CrearComprobanteSinImpuestosResult->Mensaje);
                $newInvoicingStatus->setComments($detalleApiError);
                $newInvoicingStatus->setUpdateDate(now());
                $newInvoicingStatus->save();

                // Guardar nuevo estado del comprobante en columna de grilla de ordenes
                $order->setData('facturante_invoice_status', $estadoDetalleComprobanteString);
                $order->setData('afip_last_invoice_type', $type);
                $order->save();
            }
        }
        /** Factura electrónica con detalle */
        if( $config_modo_facturacion == 2 ) {
            if( $productosConGtinFaltantes == 0 ) {
                Mage::log($requestCrearComprobante, null,'debug1.log');
//                die;
                $responseCrearComprobante = $client->CrearComprobanteSinImpuestosMtx( $requestCrearComprobante );
                Mage::log($responseCrearComprobante, null,'debug1.log');
                /**
                 * Luego de la llamada para crear el comprobante, solicito los detalles del comprobante para saber en que estado se encuentra.
                 */
                $comprobanteId = $responseCrearComprobante->CrearComprobanteSinImpuestosMtxResult->IdComprobante;
                if (!empty($comprobanteId)) {
                    $newInvoicingStatus = Mage::getModel('facturante_invoicing/order_invoicingstatus');
                    $newInvoicingStatus->setOrderId($order->getId());
                    $newInvoicingStatus->setOrderIncrementId($order->getIncrementId());
                    $newInvoicingStatus->setIdcomprobante($comprobanteId);
                    $detalleComprobante = $this->getDetalleComprobante($auth, $comprobanteId, $client);

                    $estadoDetalleComprobante = $detalleComprobante->DetalleComprobanteResult->Comprobante->EstadoComprobante;

                    $estadoDetalleComprobanteString = $this->getStringEstadoComprobante($estadoDetalleComprobante);
                    $newInvoicingStatus->setStatus($estadoDetalleComprobanteString);
                    if($estadoDetalleComprobante == 4) {
                        $newInvoicingStatus->setLink($detalleComprobante->DetalleComprobanteResult->Comprobante->URLPDF);
                    }
                    /** Aqui se guarda el mensaje de respuesta de la API */
                    $detalleApiError = $this->getDetalleApiRespuesta($responseCrearComprobante->CrearComprobanteSinImpuestosMtxResult->Mensaje);
                    $newInvoicingStatus->setComments($detalleApiError);
                    $newInvoicingStatus->setUpdateDate(now());
                    $newInvoicingStatus->save();

                    // Guardar nuevo estado del comprobante en columna de grilla de ordenes
                    if( !empty( $estadoDetalleComprobanteString ) ) {
                        $order->setData('facturante_invoice_status', $estadoDetalleComprobanteString);
                    }
                    $order->setData('afip_last_invoice_type', $type);
                    $order->save();
                }
            } elseif( $productosConGtinFaltantes > 0 ) {
                /** En caso de existir productos con gtin faltantes, evito la llamada a la api y solo realizo la devolución de un mensaje de error. */
                $responseCrearComprobante = (Object) [
                    'CrearComprobanteSinImpuestosMtxResult' => (Object) [
                        'Codigo' => 'GTIN Faltante',
                        'Estado' => 'Error',
                        'Mensaje' => 'Existen productos agregados en la orden que no tienen su código GTIN.',
                        'IdComprobante' => null
                    ]
                ];
            }
        }

        return $responseCrearComprobante;
    }

    /** Recibe el codigo de estado entregado por la api y lo traduce a string */
    public function getStringEstadoComprobante($codigo) {
        switch($codigo) {
            case 8: return "Esperando CAE";
                break;
            case 2: return "Enviando";
                break;
            case 4: return "Procesado";
                break;
            case 6: return "Error en Comprobante";
                break;
            case 3: return "Error en Envío";
                break;
            default:
                break;
        }
        return "";
    }
    /** Esta funcion recibe el mensaje de respuesta de la api y devuelve un string de estado para la orden a nivel global */
        public function getStringEstadoOrden($codigo) {
        switch($codigo) {
            case "Esperando CAE": return "Pendiente";
                break;
            case "Enviando": return "Procesando";
                break;
            case "Procesado": return "Procesado";
                break;
            case "Error en Envío": return "Error en Envío de E-mail";
                break;
            case "Error en Comprobante": return "Error en Comprobante";
                break;
            default:
                break;
        }
        return "";
    }

    /**
     * Recibe $auth que son los datos de autenticacion, $idComprobante y $clienteSoap que es la instancia del web Service
     * Devuelve el estado del comprobante actualmente.
     *
     */
    public function getDetalleComprobante($auth, $idComprobante, $clienteSoap) {
        $paramDetalleComprobante = array(
            "Autenticacion" => $auth,
            "IdComprobante" => $idComprobante,
        );
        $requestDetalleComprobante = array('request' => $paramDetalleComprobante);
        $responseDetalleComprobante = $clienteSoap->DetalleComprobante($requestDetalleComprobante);

        return $responseDetalleComprobante;
    }

    /** Recibe el codigo de mensaje de la api y devuelve la descripcion */
    public function getDetalleApiRespuesta($codMensaje) {
            return $codMensaje;
    }

    /**
     * Obtener fecha actual en formato "1900-12-03T00:00:00", ejemplo: "2016-01-20T12:24:14"
     *
     * @return string
     */
    public function getCurrentFormattedDate()
    {
        $currentDate = date(DATE_ATOM, time());
        $currentDate = substr($currentDate,0,-6);

        return $currentDate;
    }

    /**
     * Validar si numero es DNI o CUIT basandose en numero de digitos
     *
     * @param int $number
     * @return boolean
     */
    public function isCUIT($number) {
        if ( strlen( strval( $number ) ) == 11 ) {
            $cuitValido = self::validarCuit($number);
            return $cuitValido;
        }
        return false;
    }

    /**
     * Validar si el CUIT es valido
     *
     * @param int $cuit
     * @return boolean
     */
    public function validarCuit( $cuit ) {
        $cuit = preg_replace( '/[^\d]/', '', (string) $cuit );
        if( strlen( $cuit ) != 11 ){
            return false;
        }
        $acumulado = 0;
        $digitos = str_split( $cuit );
        $digito = array_pop( $digitos );

        for( $i = 0; $i < count( $digitos ); $i++ ){
            $acumulado += $digitos[ 9 - $i ] * ( 2 + ( $i % 6 ) );
        }
        $verif = 11 - ( $acumulado % 11 );
        $verif = $verif == 11? 0 : $verif;

        return $digito == $verif;
    }
}
