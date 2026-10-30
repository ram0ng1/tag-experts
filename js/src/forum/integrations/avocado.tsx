import { extend } from 'flarum/common/extend';
import Tooltip from 'flarum/common/components/Tooltip';
import type Mithril from 'mithril';

import { tText } from '../../common/utils/experts';

type Vnode = Mithril.Vnode<any, any>;

/** Primeiro vnode (em profundidade) que passa no teste. */
function findVnode(node: unknown, test: (vnode: Vnode) => boolean): Vnode | null {
  if (!node || typeof node !== 'object') return null;

  if (Array.isArray(node)) {
    for (const child of node) {
      const found = findVnode(child, test);
      if (found) return found;
    }

    return null;
  }

  const vnode = node as Vnode;

  return test(vnode) ? vnode : findVnode(vnode.children, test);
}

/**
 * Os cartões de discussão do ramon/avocado montam os próprios selos (fixada,
 * trancada, seguindo) em vez de usar `discussion.badges()`. O selo de
 * resposta de especialista entra na mesma linha, com o mesmo visual. Sem o
 * Avocado o módulo nunca carrega e nada roda.
 */
export default function addAvocadoBadges() {
  extend('ext:ramon/avocado/forum/components/shared/ThreadCard', 'view', function (this: any, vnode: Vnode) {
    const discussion = this.attrs.discussion;

    if (!discussion?.attribute?.('expertPostNumber')) return;

    const meta = findVnode(vnode, (node) => typeof node.attrs?.className === 'string' && /-threadMeta\b/.test(node.attrs.className));

    if (!meta || !Array.isArray(meta.children)) return;

    const label = tText('forum.discussion.expert_reply_badge');

    // Depois do último selo do tema, antes das tags.
    const children = meta.children as unknown[];
    const lastBadge = children.reduce<number>(
      (last, child, index) => (findVnode(child, (node) => /AvocadoHome-badge\b/.test(node.attrs?.className || '')) ? index : last),
      -1
    );
    const at = lastBadge >= 0 ? lastBadge + 1 : children.length;

    children.splice(
      at,
      0,
      <Tooltip text={label} position="top">
        <span className="AvocadoHome-badge AvocadoHome-badge--tagExpertReply" aria-label={label}>
          <i className="fas fa-user-graduate" aria-hidden="true" />
        </span>
      </Tooltip>
    );
  });
}
