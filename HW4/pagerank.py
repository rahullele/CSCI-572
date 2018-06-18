import networkx as nx

G = nx.read_edgelist("Edgelist.txt",create_using=nx.DiGraph())
pr = nx.pagerank(G, alpha=0.85, personalization=None, max_iter=30, tol=1e-06, nstart=None, weight='weight', dangling=None)

prFile = open("external_pageRankFile.txt","w")

for id in pr:
	prFile.write("/Users/rahul/solr-7.2.1/example/exampledocs/crawl_data/" + id + "=" + str(pr[id]))
	prFile.write("\n")

prFile.close()
