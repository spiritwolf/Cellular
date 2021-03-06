<?php
/**
 * Cellular Framework
 * 微信支付接口
 * @copyright Cellular Team
 */

namespace ext\wechat;

class Pay
{
    /**
     * 统一下单
     * 除被扫支付场景以外，商户系统先调用该接口在微信支付服务后台生成预支付交易单，返回正确的预支付交易回话标识后再按扫码、JSAPI、APP等不同场景生成交易串调起支付。
     * @return bool
     */
    public static function unifiedOrder($param)
    {
        /***
        字段                          名称          类型
        $param['appid']            # 公众账号ID     string(32)  *  微信分配的公众账号ID（企业号corpid即为此appId）
        $param['mch_id']           # 商户号         string(32)  * 微信支付分配的商户号
        $param['device_info']      # 设备号         String(32)    终端设备号(门店号或收银设备ID)，注意：PC网页或公众号内支付请传"WEB"
        $param['nonce_str']        # 随机字符串     String(32)  *  随机字符串，不长于32位。推荐随机数生成算法
        $param['sign']             # 签名          String(32)  * 签名，详见签名生成算法
        $param['body']             # 商品描述       String(128)  * 商品或支付单简要描述
        $param['detail']           # 商品详情       String(8192)   商品名称明细列表
        $param['attach']           # 附加数据       String(127)    附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $param['out_trade_no']     # 商户订单号     String(32)   * 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        $param['fee_type']         # 货币类型       String(16)     符合ISO 4217标准的三位字母代码，默认人民币：CNY，其他值列表详见货币类型
        $param['total_fee']        # 总金额         Int         *  订单总金额，单位为分，详见支付金额
        $param['spbill_create_ip'] # 终端IP        String(16)   *  APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP
        $param['time_start']       # 交易起始时间   String(14)     订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。其他详见时间规则
        $param['time_expire']      # 交易结束时间   String(14)     订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。其他详见时间规则
                                   # 注意：最短失效时间间隔必须大于5分钟
        $param['goods_tag']        # 商品标记     String(32)     商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
        $param['notify_url']       # 通知地址     String(256)  * 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数
        $param['trade_type']       # 交易类型     String(16)   * 取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
        $param['product_id']       # 商品ID      String(32)      trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
        $param['limit_pay']        # 指定支付方式 String(32)     no_credit--指定不能使用信用卡支付
        $param['openid']           # 用户标识    string(128)     trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。企业号请使用【企业号OAuth2.0接口】获取企业号内成员userid，再调用【企业号userid转openid接口】进行转换
        $param['api_key']          # API 密钥    string       * 生成签名需要
        ***/

        /*
        <xml>
        <return_code><![CDATA[SUCCESS]]></return_code>
        <return_msg><![CDATA[OK]]></return_msg>
        <appid><![CDATA[wx6b4b5a99a75ee85b]]></appid>
        <mch_id><![CDATA[1321061001]]></mch_id>
        <device_info><![CDATA[WEB]]></device_info>
        <nonce_str><![CDATA[IZps5LqnbvnBHtvQ]]></nonce_str>
        <sign><![CDATA[874126F658C1139D2E3F1AD8E4BF6446]]></sign>
        <result_code><![CDATA[SUCCESS]]></result_code>
        <prepay_id><![CDATA[wx20160407174507dde8ef48e30112760132]]></prepay_id>
        <trade_type><![CDATA[NATIVE]]></trade_type>
        <code_url><![CDATA[weixin://wxpay/bizpayurl?pr=RZvMZTN]]></code_url>
        </xml>
        */

        /*
         * http://paysdk.weixin.qq.com/example/qrcode.php?data=weixin://wxpay/bizpayurl?pr=RZvMZTN
         */

        if (!array_key_exists('appid', $param)) die('appid is empty');
        if (!array_key_exists('mch_id', $param)) die('mch_id is empty');
        if (!array_key_exists('nonce_str', $param)) die('nonce_str is empty');
        if (!array_key_exists('sign', $param)) die('sign is empty');
        if (!array_key_exists('body', $param)) die('body is empty');
        if (!array_key_exists('out_trade_no', $param)) die('out_trade_no is empty');
        if (!array_key_exists('total_fee', $param)) die('total_fee is empty');
        if (!array_key_exists('spbill_create_ip', $param)) die('spbill_create_ip is empty');
        if (!array_key_exists('notify_url', $param)) die('notify_url is empty');
        if (!array_key_exists('trade_type', $param)) die('trade_type is empty');
        if ($param['trade_type'] == 'JSAPI' && !array_key_exists('openid', $param)) die ('trade_type=JSAPI openid is empty');
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $callback = common::postXmlCurl(common::arrayToXml($param), $url);
        $callback = simplexml_load_string($callback);
        if ($callback->return_code == 'SUCCESS' && $callback->result_code == 'SUCCESS') {
            return $callback;
        } else {
            die('wechat.pay.unifiedOrder error: ' . $callback->err_code . ' ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 查询订单
     * 该接口提供所有微信支付订单的查询，商户可以通过该接口主动查询订单状态，完成下一步的业务逻辑。
     * 需要调用查询接口的情况：
     * 当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知；
     * 调用支付接口后，返回系统错误或未知交易状态情况；
     * 调用被扫支付API，返回USERPAYING的状态；
     * 调用关单或撤销接口API之前，需确认支付状态；
     * @param $param
     * @return bool
     */
    public static function orderQuery($param)
    {
        /***
        字段                        名称      必填   类型         示例值                            描述
        $param['appid']          # 公众账号ID 是     string(32)  wxd678efh567hg6787               微信分配的公众账号ID（企业号corpid即为此appId）
        $param['mch_id']         # 商户号     是     string(32)  1230000109                       微信支付分配的商户号
        $param['transaction_id'] # 微信订单号  二选一 string(32)  1009660380201506130728806387     微信的订单号，优先使用
        $param['out_trade_no']   # 商户订单号  二选一 string(32)  20150806125346                   商户系统内部的订单号，当没提供transaction_id时需要传这个。
        $param['nonce_str']      # 随机字符串  是    string(32)  C380BEC2BFD727A4B6845133519F3AD6 随机字符串，不长于32位。推荐随机数生成算法
        $param['sign']           # 签名       是    string(32)  5K8264ILTKCH16CQ2502SI8ZNMTM67VS 签名，详见签名生成算法
        ***/

        if (!array_key_exists('appid', $param)) die('appid is empty');
        if (!array_key_exists('mch_id', $param)) die('mch_id is empty');
        if (!array_key_exists('transaction_id', $param) && !array_key_exists('out_trade_no', $param)) die('transaction_id & out_trade_no is empty');
        if (!array_key_exists('nonce_str', $param)) die('nonce_str is empty');
        if (!array_key_exists('sign', $param)) die('sign is empty');
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $callback = common::postXmlCurl(common::arrayToXml($param), $url);
        $callback = simplexml_load_string($callback);
        if ($callback->return_code == 'SUCCESS' && $callback->result_code == 'SUCCESS') {
            return $callback;
        } else {
            die('wechat.pay.orderQuery error: ' . $callback->err_code . ' ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 关闭订单
     * 以下情况需要调用关单接口：商户订单支付失败需要生成新单号重新发起支付，要对原订单号调用关单，避免重复支付；系统下单后，用户支付超时，系统退出不再受理，避免用户继续，请调用关单接口。
     * 注意：订单生成后不能马上调用关单接口，最短调用时间间隔为5分钟。
     * @param $param
     * @return bool|mixed
     */
    public static function closeOrder($param)
    {
        /***
         * 字段                   名称        必填 类型        示例值                            描述
         * $param['appid']        #公众账号ID 是   String(32) wx8888888888888888               微信分配的公众账号ID（企业号corpid即为此appId）
         * $param['mch_id']       #商户号     是   String(32) 1900000109                       微信支付分配的商户号
         * $param['out_trade_no'] #商户订单号 是   String(32) 1217752501201407033233368018     商户系统内部的订单号
         * $param['nonce_str']    #随机字符串 是   String(32) 5K8264ILTKCH16CQ2502SI8ZNMTM67VS 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见安全规范
         * $param['sign']         #签名      是   String(32) C380BEC2BFD727A4B6845133519F3AD6 签名，详见签名生成算法
         ***/

        if (!array_key_exists('appid', $param)) die('appid is empty');
        if (!array_key_exists('mch_id', $param)) die('mch_id is empty');
        if (!array_key_exists('out_trade_no', $param)) die('out_trade_no is empty');
        if (!array_key_exists('nonce_str', $param)) die('nonce_str is empty');
        if (!array_key_exists('sign', $param)) die('sign is empty');
        $url = 'https://api.mch.weixin.qq.com/pay/closeorder';
        $callback = common::postXmlCurl(common::arrayToXml($param), $url);
        $callback = simplexml_load_string($callback);
        if ($callback->return_code == 'SUCCESS' && $callback->result_code == 'SUCCESS') {
            return $callback;
        } else {
            die('wechat.pay.closeOrder error: ' . $callback->err_code . ' ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 申请退款
     * 当交易发生之后一段时间内，由于买家或者卖家的原因需要退款时，卖家可以通过退款接口将支付款退还给买家，微信支付将在收到退款请求并且验证成功之后，按照退款规则将支付款按原路退到买家帐号上。
     * 注意：
     * 1、交易时间超过一年的订单无法提交退款；
     * 2、微信支付退款支持单笔交易分多次退款，多次退款需要提交原支付订单的商户订单号和设置不同的退款单号。一笔退款失败后重新提交，要采用原来的退款单号。总退款金额不能超过用户实际支付金额。
     * @return bool
     */
    public static function refund()
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $callback = Common::curl($url);
        $callback = json_decode($callback);
        if (empty($callback->errcode)) {
            return $callback->access_token;
        } else {
            die('wechat error: [' . $callback->errcode . '] ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 查询退款
     * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
     * @return bool
     */
    public static function refundQuery()
    {
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        $callback = Common::curl($url);
        $callback = json_decode($callback);
        if (empty($callback->errcode)) {
            return $callback->access_token;
        } else {
            die('wechat error: [' . $callback->errcode . '] ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 下载对账单
     * 商户可以通过该接口下载历史交易清单。比如掉单、系统错误等导致商户侧和微信侧数据不一致，通过对账单核对后可校正支付状态。
     * 注意：
     * 1、微信侧未成功下单的交易不会出现在对账单中。支付成功后撤销的交易会出现在对账单中，跟原支付单订单号一致，bill_type为REVOKED；
     * 2、微信在次日9点启动生成前一天的对账单，建议商户10点后再获取；
     * 3、对账单中涉及金额的字段单位为“元”。
     * @return bool
     */
    public static function downloadBill()
    {
        $url = 'https://api.mch.weixin.qq.com/pay/downloadbill';
        $callback = Common::curl($url);
        $callback = json_decode($callback);
        if (empty($callback->errcode)) {
            return $callback->access_token;
        } else {
            die('wechat error: [' . $callback->errcode . '] ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 测速上报
     * 商户在调用微信支付提供的相关接口时，会得到微信支付返回的相关信息以及获得整个接口的响应时间。为提高整体的服务水平，协助商户一起提高服务质量，微信支付提供了相关接口调用耗时和返回信息的主动上报接口，微信支付可以根据商户侧上报的数据进一步优化网络部署，完善服务监控，和商户更好的协作为用户提供更好的业务体验。
     * @return bool
     */
    public static function report()
    {
        $url = 'https://api.mch.weixin.qq.com/payitil/report';
        $callback = Common::curl($url);
        $callback = json_decode($callback);
        if (empty($callback->errcode)) {
            return $callback->access_token;
        } else {
            die('wechat error: [' . $callback->errcode . '] ' . $callback->errmsg);
        }
        return false;
    }

    /**
     * 转换短链接
     * 该接口主要用于扫码原生支付模式一中的二维码链接转成短链接(weixin://wxpay/s/XXXXXX)，减小二维码数据量，提升扫描速度和精确度。
     * @return bool
     */
    public static function shortURL()
    {
        $url = 'https://api.mch.weixin.qq.com/tools/shorturl';
        $callback = Common::curl($url);
        $callback = json_decode($callback);
        if (empty($callback->errcode)) {
            return $callback->access_token;
        } else {
            die('wechat error: [' . $callback->errcode . '] ' . $callback->errmsg);
        }
        return false;
    }
}