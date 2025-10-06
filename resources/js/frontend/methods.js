function Tamara() {
    return (
        <div>
            <p>Pay securely through PayTabs secure servers.</p>
            <p>
                Monthly payments, No late fees.
                &nbsp;
                <a href="https://tamara.co" target="_blank" className="see-more-link"
                    style={{
                    }}>More options</a>
            </p>
        </div>
    );
};


export default function FrontComponent(props) {
    switch (props.name) {
        case 'Tamara':
            return <Tamara />;

        default:
            console.log('No component:', props.name);
            return <></>;
    }
};
