var op_asset_settings = (function($){
	var cp_styles = [3, 4];
	return {
		help_vids: {
			step_1: {
				url: 'http://op2-inapp.s3.amazonaws.com/elements-feature-block.mp4',
				width: '600',
				height: '341'
			},
			step_2: {
				url: 'http://op2-inapp.s3.amazonaws.com/elements-feature-block.mp4',
				width: '600',
				height: '341'
			},
			step_3: {
				url: 'http://op2-inapp.s3.amazonaws.com/elements-feature-block.mp4',
				width: '600',
				height: '341'
			}
		},
		attributes: {
			step_1: {
				style: {
					type: 'style-selector',
					folder: 'previews',
					addClass: 'op-disable-selected',
					events: {
						change: function(value,steps){
							var show = 'icon',
								hide = 'image',
								$selected_style = steps[0].find('.op-asset-dropdown-list .selected'),
								style = parseInt($selected_style.find('img').attr('alt')),
								$colorFields = steps[1].find('.multirow-container .field-color'),
								isCPStyle = false;

							//Decide whether we should show or hide the image field or the
							//icon field in each of the items
							if(value == 'image'){
								show = 'image';
								hide = 'icon';
							}
							
							//This simply shows and hides the fields decided on above
							steps[1].find('.multirow-container').find('.field-'+hide).hide().end().find('.field-'+show).show();
							
							//See if this is a style that contains a color picker
							for(i = 0; i<cp_styles.length; i++){
								if (cp_styles[i]==style){
									isCPStyle = true;
									break;
								}
							}
							
							//If the style is in the color picker styles array then we must
							//show the color picker in each of the color fields. Otherwise we
							//hide it and clear the value
							if (isCPStyle) $colorFields.show(); else $colorFields.hide().find('.color-picker-container input').val('');
						}
					}
				}
			},
			step_2: {
				columns: {
					title: 'columns',
					type: 'select',
					valueRange: {start:1,finish:4}
				},
				elements: {
					type: 'multirow',
					multirow: {
						attributes: {
							icon: {
								title: 'icon',
								type: 'image-selector',
								folder: 'icons',
								selectorClass: 'icon-view-64'
							},
							bg_color: {
								title: 'bg_color',
								type: 'color',
								default_value: ''
							},
							image: {
								title: 'image',
								type: 'media'
							},
							title: {
								title: 'title'
							},
							content: {
								title: 'content',
								type: 'wysiwyg'
							}
						},
						onAdd: function(steps){
							var $selected_style = steps[0].find('.op-asset-dropdown-list .selected');
							
							$selected_style.trigger('click');
						}
					}
				}
			},
			step_3: {
				microcopy: {
					text: 'feature_block_advanced1',
					type: 'microcopy'
				},
				microcopy2: {
					text: 'advanced_warning_2',
					type: 'microcopy',
					addClass: 'warning'
				},
				font: {
					title: 'feature_block_title_styling',
					type: 'font'
				},
				content_font: {
					title: 'feature_block_content_styling',
					type: 'font'
				}
			}
		},
		insert_steps: {2:{next:'advanced_options'},3:true},
		customInsert: function(attrs){
			var str = '',
				style = (attrs.style!='image' ? 'icon' : attrs.style) || 'icon',
				icon_style = attrs.style,
				columns = attrs.columns || 2,
				field = style == 'image' ? 'image' : 'icon',
				attrs_str = '';
			
			for(var i in attrs.elements){
				var v = attrs.elements[i],
					q = v.title || '', 
					a = v.content || '',
					ic = v[field] || '';
					bg = v.bg_color || '';
				str += '[feature title="'+q.replace( /"/ig,"'")+'" '+field+'="'+ic+'" bg_color="' + bg + '"]'+a+'[/feature] ';
			};
			$.each(['font','content_font'],function(i,v){
				$.each(attrs[v],function(i2,v2){
					if(v2 != ''){
						attrs_str += ' '+v+'_'+i2+'="'+v2.replace(/"/ig,"'")+'"';
					}
				});
			});
			str = '[feature_block style="'+style+'" columns="'+columns+'" icon_style="' + (style!='image' ? icon_style : '') + '"' + attrs_str+']'+str+'[/feature_block]';
			OP_AB.insert_content(str);
			$.fancybox.close();
		},
		customSettings: function(attrs,steps){
			var features = attrs.feature || {},
				add_link = steps[1].find('.field-id-op_assets_core_feature_block_elements a.new-row'),
				feature_inputs = steps[1].find('.field-id-op_assets_core_feature_block_elements-multirow-container'),
				style = ((typeof(attrs.attrs.icon_style)!='undefined' && attrs.attrs.icon_style!='undefined' && attrs.attrs.icon_style!='') ? attrs.attrs.icon_style : attrs.attrs.style);;
				
			attrs = attrs.attrs;
			OP_AB.set_selector_value('op_assets_core_feature_block_style_container',(style || ''));
			OP_AB.set_font_settings('font',attrs,'op_assets_core_feature_block_font');
			OP_AB.set_font_settings('content_font',attrs,'op_assets_core_feature_block_content_font');
			$('#op_assets_core_feature_block_columns').val(attrs.columns || '');
			$.each(features,function(i,v){
				add_link.trigger('click');
				var cur = feature_inputs.find('.op-multirow:last'),
					attrs = v.attrs || {},
					input = (cur.find('input:eq(0)').is(':hidden') ? cur.find('input:eq(0)').parents('.field-bg_color').next('.field-image').find('input') : cur.find('input:eq(0)'));
				OP_AB.set_selector_value(cur.find('.op-asset-dropdown').attr('id'),(attrs.icon || ''));
				OP_AB.set_uploader_value(input.attr('id'),attrs.image);
				var color_container = (cur.find('input:eq(1)').hasClass('field-bg_color') ? cur.find('input:eq(1)') : cur.find('input:eq(0)'));
				color_container.val(attrs.bg_color || '').next('a').css({ backgroundColor: attrs.bg_color });
				cur.find('input:eq(2)').val(attrs.title || '');
				OP_AB.set_wysiwyg_content(cur.find('textarea').attr('id'),attrs.content || '');
			});
		}
	};
})(jQuery);