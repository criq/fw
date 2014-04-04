{% macro getDefault(url, pagination, options) %}

	{% set prevCopy  = options.prevCopy|default("Previous") %}
	{% set nextCopy  = options.nextCopy|default("Next") %}
	{% set pageIdent = options.pageIdent|default("page") %}

	{% set pages = getPages(pagination, { allPagesLimit: options.allPagesLimit, endsOffset: options.endsOffset, currentOffset: options.currentOffset }) %}

	{% if pages|length > 1 %}
		<p class="pagination">

			<span class="segment prev">
				{% if pagination.page > 1 %}
					<span class="node skip prev active"><a href="{{ getPaginationURL(url, pagination.page - 1, pageIdent) }}">{{ prevCopy }}</a></span>
				{% else %}
					<span class="node skip prev inactive">{{ prevCopy }}</span>
				{% endif %}
			</span>

			<span class="segment pages">
				{% for key, page in pages %}

					{% if page != pagination.page %}
						<span class="node page active"><a href="{{ getPaginationURL(url, page, pageIdent) }}">{{ page }}</a></span>
					{% else %}
						<span class="node page inactive current">{{ page }}</span>
					{% endif %}

					{% if page + 1 != pages[key + 1] and page + 1 <= pagination.getMaxPage %}
						<span class="node hellip">&hellip;</span>
					{% endif %}

				{% endfor %}
			</span>

			<span class="segment next">
				{% if pagination.page < pagination.getMaxPage %}
					<span class="node skip next active"><a href="{{ getPaginationURL(url, pagination.page + 1, pageIdent) }}">{{ nextCopy }}</a></span>
				{% else %}
					<span class="node skip next active">{{ nextCopy }}</span>
				{% endif %}
			</span>

		</p>
	{% endif %}

{% endmacro %}