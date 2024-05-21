
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('paytabs_blocks_data', {});

// const defaultLabel = __('PayTabs Payments');

/**
 * Content component
 */
const Content = (props) => {
	const { PaymentMethodLabel } = props.components;

	return <>
		{props.setting.description &&
			<div style={{ display: 'flex', justifyContent: 'space-between', width: "100%", paddingRight: 5, paddingLeft: 5 }}>
				<PaymentMethodLabel text={props.setting.description} />
			</div>
		}
		{props.showSaveNote &&
			<div style={{ display: 'flex', justifyContent: 'space-between', width: "100%", paddingRight: 5, paddingLeft: 5 }}>
				<strong>Will Save to Account</strong>
			</div>
		}
	</>
};


/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
	const { PaymentMethodLabel } = props.components;

	return <div style={{ display: 'flex', justifyContent: 'space-between', width: "100%", paddingRight: 5, paddingLeft: 5 }}>
		<PaymentMethodLabel text={decodeEntities(props.setting.title)} />
		{props.setting.icon != '' && <img src={props.setting.icon} alt={props.setting.name} />}
	</div>
};

let hasSubscription = settings.cart.has_subscription;
settings.blocks.forEach(setting => {
	let supportTokenization = setting.supports.includes("tokenization") && setting.enable_tokenise;
	let showSaveNote = supportTokenization && hasSubscription;
	let gateWay = {
		name: setting.name,
		label: <Label setting={setting} />,
		content: <Content setting={setting} showSaveNote={showSaveNote} />,
		edit: <div>{decodeEntities(setting.description)}</div>,
		canMakePayment: () => true,
		ariaLabel: decodeEntities(setting.title),
		supports: {
			showSavedCards: supportTokenization,
			showSaveOption: supportTokenization && !hasSubscription,
			features: setting.supports,
		},
	};

	registerPaymentMethod(gateWay);
});