import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType('bluem/custom-block', {
    edit({ attributes, setAttributes }) {
        return (
            <div {...useBlockProps()}>
                <input
                    value={attributes.message}
                    onChange={(e) => setAttributes({ message: e.target.value })}
                />
            </div>
        );
    },
    save({ attributes }) {
        return (
            <div {...useBlockProps.save()}>
                {attributes.message}
            </div>
        );
    }
});
